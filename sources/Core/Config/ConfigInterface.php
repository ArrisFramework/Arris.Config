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
     *
     * Called without arguments, returns the whole configuration.
     */
    public function get(mixed $key = null, mixed $default = null): mixed;

    /**
     * Sets a configuration value using a simple or nested key.
     */
    public function set(mixed $key, mixed $value): void;

    /**
     * Checks whether a configuration value exists.
     */
    public function has(mixed $key): bool;

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
    public function remove(mixed $key): void;

    /**
     * Merges another configuration layer.
     */
    public function merge(ConfigInterface $config): static;

    /**
     * Replaces a node by path or the whole configuration.
     */
    public function replace(array|string $target, mixed $value = null): static;
}
