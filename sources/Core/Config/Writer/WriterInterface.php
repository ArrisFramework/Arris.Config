<?php

namespace Arris\Core\Config\Writer;

use Arris\Core\Config\Exception\WriteException;

/**
 * Config file parser interface.
 *
 * @package    Config
 * @license    MIT
 */
interface WriterInterface
{
    /**
     * Writes a configuration from `$config` to `$filename`.
     *
     * @param  array $config
     * @param  string $filename
     *
     * @throws WriteException if the data could not be written to the file
     *
     * @return array
     */
    public function toFile($config, $filename);

    /**
     * Writes a configuration from `$config` to a string.
     *
     * @param  array $config
     * @param bool $pretty
     *
     * @return string
     */
    public function toString($config, bool $pretty = true);

    /**
     * Returns an array of allowed file extensions for this writer.
     *
     * @return array
     */
    public static function getSupportedExtensions();
}
