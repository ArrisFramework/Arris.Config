<?php
declare(strict_types=1);

namespace Arris\Core\Config;

use ArrayAccess;
use InvalidArgumentException;
use Iterator;

/**
 * Abstract Config class.
 *
 * Values are addressed with dot notation. Dots are structural only in path
 * expressions: `\.` addresses a literal dot in a data key. Data keys are
 * always literal; nothing is expanded on write.
 */
abstract class AbstractConfig implements ArrayAccess, ConfigInterface, Iterator
{
    /**
     * Stores the configuration data.
     */
    public ?array $data = null;

    /**
     * Caches resolved keys. Fully invalidated on every mutation.
     */
    protected array $cache = [];

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
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        if ($key === '*') {
            return $this->data ?? [];
        }

        if ($this->has($key)) {
            return $this->cache[$key];
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     *
     * Strict set: the kind of an existing node cannot change. Use replace()
     * for an explicit replacement.
     */
    public function set($key, $value): void
    {
        $data = $this->data ?? [];
        KeyPath::set($data, (string)$key, $value);
        $this->data = $data;
        $this->cache = [];
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
            $this->cache = [];

            return $this;
        }

        if (func_num_args() !== 2) {
            throw new InvalidArgumentException('replace(string $path, $value) requires exactly two arguments');
        }

        KeyPath::replace($data, $target, $value);
        $this->data = $data;
        $this->cache = [];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function has($key): bool
    {
        if (array_key_exists($key, $this->cache)) {
            return true;
        }

        [$found, $value] = KeyPath::get($this->data ?? [], (string)$key);

        if ($found) {
            $this->cache[$key] = $value;
        }

        return $found;
    }

    /**
     * Merge a configuration layer. Null in the incoming data deletes a key.
     */
    public function merge(ConfigInterface $config): ConfigInterface
    {
        $this->data = KeyPath::mergePatch($this->data ?? [], $config->all());
        $this->cache = [];

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
     * Gets a value using the offset as a key.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Checks if a key exists.
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Sets a value using the offset as a key.
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            throw new InvalidArgumentException('Config keys cannot be appended without a key');
        }

        $this->set($offset, $value);
    }

    /**
     * Deletes a key and its value.
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Returns the data array element referenced by its internal cursor.
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return (is_array($this->data) ? current($this->data) : null);
    }

    /**
     * Returns the data array index referenced by its internal cursor.
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return (is_array($this->data) ? key($this->data) : null);
    }

    /**
     * Moves the data array's internal cursor forward one element.
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        return (is_array($this->data) ? next($this->data) : null);
    }

    /**
     * Moves the data array's internal cursor to the first element.
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        return (is_array($this->data) ? reset($this->data) : null);
    }

    /**
     * Tests whether the iterator's current index is valid.
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return (is_array($this->data) ? key($this->data) !== null : false);
    }

    /**
     * Remove a value using the offset as a key.
     */
    public function remove($key): void
    {
        $data = $this->data ?? [];
        KeyPath::remove($data, (string)$key);
        $this->data = $data;
        $this->cache = [];
    }
}
