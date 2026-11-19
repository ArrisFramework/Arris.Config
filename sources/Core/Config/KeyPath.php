<?php
declare(strict_types=1);

namespace Arris\Core\Config;

use Arris\Core\Config\Exception\StructureConflictException;

/**
 * Dot-notation path helper.
 *
 * An unescaped dot separates path segments; `\.` is a literal dot inside a
 * segment and `\\` is a literal backslash.
 *
 * Data keys are always literal. Dots are structural only in path expressions.
 *
 * Node kinds: missing, null, scalar, list, assoc. An empty array is a special
 * "empty" kind: it can become any kind and can clear an existing array.
 */
final class KeyPath
{
    /**
     * Splits a path into segments, honouring `\.` and `\\` escapes.
     *
     * @return string[]
     */
    public static function segments(string $path): array
    {
        $segments = [];
        $current = '';
        $length = strlen($path);

        for ($i = 0; $i < $length; $i++) {
            $char = $path[$i];

            if ($char === '\\' && $i + 1 < $length) {
                $next = $path[$i + 1];
                if ($next === '.' || $next === '\\') {
                    $current .= $next;
                    $i++;
                    continue;
                }
            }

            if ($char === '.') {
                $segments[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $segments[] = $current;

        return $segments;
    }

    /**
     * Looks up a path.
     *
     * @return array{0: bool, 1: mixed} [found, value]
     */
    public static function get(array $data, string $path): array
    {
        $node = $data;

        foreach (self::segments($path) as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return [false, null];
            }
            $node = $node[$segment];
        }

        return [true, $node];
    }

    /**
     * Strict set: the kind of an existing node cannot change.
     *
     * null is a normal value. Assigning null over a non-empty array/list is a
     * kind change and throws; replace() can be used for an explicit replacement.
     *
     * @throws StructureConflictException
     */
    public static function set(array &$data, string $path, mixed $value): void
    {
        $segments = self::segments($path);
        $last = array_pop($segments);
        $node = &$data;

        foreach ($segments as $segment) {
            $node = &self::descend($node, $segment, $path);
        }

        self::assertSegmentAllowed($node, $last, $path);

        if (array_key_exists($last, $node)) {
            $node[$last] = self::setNode($node[$last], $value, $path);
        } else {
            $node[$last] = $value;
        }
    }

    /**
     * Explicit replace: the target node kind is ignored, the value is assigned as-is.
     * Unlike set(), null is a valid value.
     *
     * @throws StructureConflictException
     */
    public static function replace(array &$data, string $path, mixed $value): void
    {
        $segments = self::segments($path);
        $last = array_pop($segments);
        $node = &$data;

        foreach ($segments as $segment) {
            $node = &self::descend($node, $segment, $path);
        }

        self::assertSegmentAllowed($node, $last, $path);

        $node[$last] = $value;
    }

    /**
     * Removes a key by path. Empty parent arrays are kept as empty nodes.
     */
    public static function remove(array &$data, string $path): void
    {
        $segments = self::segments($path);
        $last = array_pop($segments);
        $node = &$data;

        foreach ($segments as $segment) {
            if (!isset($node[$segment]) || !is_array($node[$segment])) {
                return;
            }
            $node = &$node[$segment];
        }

        unset($node[$last]);
    }

    /**
     * Merges two data layers where null in the patch is a value.
     */
    public static function mergeBase(array $base, array $patch): array
    {
        if (self::replacesRoot($base, $patch)) {
            return $patch;
        }

        return self::mergeLayers($base, $patch, false);
    }

    /**
     * Merges two data layers where null in the patch deletes the key.
     */
    public static function mergePatch(array $base, array $patch): array
    {
        if (self::replacesRoot($base, $patch)) {
            return $patch;
        }

        return self::mergeLayers($base, $patch, true);
    }

    /**
     * A list at the root always replaces the base; an empty patch is a no-op.
     * Only two non-empty associative arrays are merged key by key.
     */
    private static function replacesRoot(array $base, array $patch): bool
    {
        if ($patch === []) {
            return false;
        }

        if (array_is_list($patch)) {
            return true;
        }

        return $base !== [] && array_is_list($base);
    }

    /**
     * Kind of a node: missing, null, empty, scalar, list, assoc.
     */
    public static function kind(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (!is_array($value)) {
            return 'scalar';
        }

        if ($value === []) {
            return 'empty';
        }

        return array_is_list($value) ? 'list' : 'assoc';
    }

    private static function setNode(mixed $existing, mixed $value, string $path): mixed
    {
        $existingKind = self::kind($existing);

        if ($value === null) {
            if ($existingKind === 'list' || $existingKind === 'assoc') {
                throw self::conflict($path, $existingKind, 'null');
            }

            return null;
        }

        $valueKind = self::kind($value);

        if ($existingKind === 'null' || $existingKind === 'empty') {
            return $value;
        }

        if ($existingKind === 'scalar') {
            if ($valueKind === 'scalar') {
                return $value;
            }

            throw self::conflict($path, $existingKind, $valueKind);
        }

        // Existing is a non-empty array (list or assoc).
        if ($valueKind === 'scalar') {
            throw self::conflict($path, $existingKind, $valueKind);
        }

        if ($valueKind === 'empty') {
            return [];
        }

        if ($existingKind === 'list' && $valueKind === 'list') {
            return $value;
        }

        if ($existingKind === 'assoc' && $valueKind === 'assoc') {
            return self::mergeStrict($existing, $value, $path);
        }

        throw self::conflict($path, $existingKind, $valueKind);
    }

    /**
     * @throws StructureConflictException
     */
    private static function mergeStrict(array $base, array $patch, string $path): array
    {
        foreach ($patch as $key => $value) {
            $childPath = $path === '' ? (string)$key : $path . '.' . $key;

            if (!array_key_exists($key, $base)) {
                $base[$key] = $value;
                continue;
            }

            $base[$key] = self::setNode($base[$key], $value, $childPath);
        }

        return $base;
    }

    /**
     * @return array reference to the child node
     *
     * @throws StructureConflictException
     */
    private static function &descend(array &$node, string $segment, string $path): array
    {
        self::assertSegmentAllowed($node, $segment, $path);

        if (!array_key_exists($segment, $node)) {
            $node[$segment] = [];
        }

        $kind = self::kind($node[$segment]);

        if ($kind === 'null') {
            $node[$segment] = [];
        } elseif ($kind === 'scalar') {
            throw new StructureConflictException(sprintf(
                'Cannot access "%s": node "%s" is scalar, a branch is required.',
                $path,
                $segment
            ));
        }

        return $node[$segment];
    }

    private static function assertSegmentAllowed(array $node, string $segment, string $path): void
    {
        if ($node !== [] && array_is_list($node) && !preg_match('/^\d+$/D', $segment)) {
            throw new StructureConflictException(sprintf(
                'Cannot access "%s": list nodes accept only numeric segments, got "%s".',
                $path,
                $segment
            ));
        }
    }

    private static function mergeLayers(array $base, array $patch, bool $nullDeletes): array
    {
        foreach ($patch as $key => $value) {
            if ($value === null) {
                if ($nullDeletes) {
                    unset($base[$key]);
                } else {
                    $base[$key] = null;
                }
                continue;
            }

            if (!array_key_exists($key, $base)) {
                $base[$key] = $value;
                continue;
            }

            $baseKind = self::kind($base[$key]);
            $patchKind = self::kind($value);

            if ($baseKind === 'assoc' && $patchKind === 'assoc') {
                $base[$key] = self::mergeLayers($base[$key], $value, $nullDeletes);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    private static function conflict(string $path, string $existingKind, string $newKind): StructureConflictException
    {
        return new StructureConflictException(sprintf(
            'Cannot set "%s": existing node is %s, new value is %s. Use replace() to change the node kind.',
            $path,
            $existingKind === 'empty' ? 'empty array' : $existingKind,
            $newKind === 'empty' ? 'empty array' : $newKind
        ));
    }
}
