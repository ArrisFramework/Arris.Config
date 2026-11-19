<?php
declare(strict_types=1);

namespace Arris\Core\Config;

use ArrayAccess;
use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;
use Arris\Core\Config\Exception\NotImplementedException;

/**
 * Abstract Config class.
 *
 * Values are addressed with dot notation. Dots are structural only in path
 * expressions: `\.` addresses a literal dot in a data key. Data keys are
 * always literal; nothing is expanded on write.
 */
abstract class AbstractConfig implements ArrayAccess, ConfigInterface, IteratorAggregate
{
    /**
     * Stores the configuration data.
     */
    protected ?array $data = null;

    public function __construct(array $data = [])
    {
        $this->data = KeyPath::mergeBase($this->getDefaults(), $data);
    }

    /**
     * Override this method in your own subclass to provide an array of default
     * options and values.
     *
     * @codeCoverageIgnore
     */
    protected function getDefaults(): array
    {
        return [];
    }

    /**
     * Replaces the internal data array. Kept for subclasses and factories.
     */
    protected function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function get(mixed $key = null, mixed $default = null): mixed
    {
        if (func_num_args() === 0) {
            return $this->getAll();
        }

        [$found, $value] = KeyPath::get($this->data ?? [], self::normalizeKey($key));

        return $found ? $value : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(): array
    {
        return $this->data ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAllMatch(string $mask): array
    {
        throw new NotImplementedException('getAllMatch() is not implemented yet');
    }

    /**
     * {@inheritDoc}
     *
     * Strict set: the kind of an existing node cannot change. Use replace()
     * for an explicit replacement.
     */
    public function set(mixed $key, mixed $value): void
    {
        $data = $this->data ?? [];
        KeyPath::set($data, self::normalizeKey($key), $value);
        $this->data = $data;
    }

    /**
     * Replaces a node by path or the whole configuration.
     *
     * replace(['a' => 1])      — replace the whole config
     * replace('a.b', $value)   — replace the node 'a.b' (null is a valid value)
     *
     * @throws InvalidArgumentException
     */
    public function replace(array|string $target, mixed $value = null): static
    {
        $data = $this->data ?? [];

        if (is_array($target)) {
            if (func_num_args() !== 1) {
                throw new InvalidArgumentException('replace(array) does not accept a value argument');
            }

            $this->data = $target;

            return $this;
        }

        if (func_num_args() !== 2) {
            throw new InvalidArgumentException('replace(string $path, $value) requires exactly two arguments');
        }

        KeyPath::replace($data, $target, $value);
        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function has(mixed $key): bool
    {
        [$found] = KeyPath::get($this->data ?? [], self::normalizeKey($key));

        return $found;
    }

    /**
     * Merge a configuration layer. Null in the incoming data deletes a key.
     */
    public function merge(ConfigInterface $config): static
    {
        $this->data = KeyPath::mergePatch($this->data ?? [], $config->all());

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->data ?? [];
    }

    /**
     * Returns a fresh iterator over the configuration data.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data ?? []);
    }

    /**
     * Checks if a key exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Gets a value using the offset as a key.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Sets a value using the offset as a key.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            throw new InvalidArgumentException('Config keys cannot be appended without a key');
        }

        $this->set($offset, $value);
    }

    /**
     * Deletes a key and its value.
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    /**
     * Remove a value using the offset as a key.
     */
    public function remove(mixed $key): void
    {
        $data = $this->data ?? [];
        KeyPath::remove($data, self::normalizeKey($key));
        $this->data = $data;
    }

    private static function normalizeKey(mixed $key): string
    {
        if (!is_string($key) && !is_int($key)) {
            throw new InvalidArgumentException('Config keys must be strings or integers');
        }

        return (string)$key;
    }
}
