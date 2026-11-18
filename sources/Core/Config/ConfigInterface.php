<?php
declare(strict_types=1);

namespace Arris\Core\Config;

/**
 * Config interface.
 */
interface ConfigInterface
{
    /**
     * Gets a configuration setting using a simple or nested key.
     */
    public function get($key, $default = null);

    /**
     * Sets a configuration value using a simple or nested key.
     */
    public function set($key, $value): void;

    /**
     * Checks whether a configuration value exists.
     */
    public function has($key): bool;

    /**
     * Returns the whole configuration.
     */
    public function all(): array;

    /**
     * Returns the whole configuration.
     */
    public function getAll(): array;

    /**
     * Returns configuration values matching a mask.
     *
     * @throws \Arris\Core\Config\Exception\NotImplementedException
     */
    public function getAllMatch(string $mask): array;

    /**
     * Removes a configuration value.
     */
    public function remove($key): void;

    /**
     * Merges another configuration layer.
     */
    public function merge(ConfigInterface $config): ConfigInterface;

    /**
     * Replaces a node by path or the whole configuration.
     */
    public function replace(array|string $target, mixed $value = null);
}
