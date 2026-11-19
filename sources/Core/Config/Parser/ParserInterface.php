<?php
declare(strict_types=1);

namespace Arris\Core\Config\Parser;

/**
 * Config file parser interface
 *
 * @package    Config
 * @license    MIT
 */
interface ParserInterface
{
    /**
     * Parsers must be constructible without arguments: Config instantiates
     * every parser via `new $class()`.
     */
    public function __construct();

    /**
     * Parses a configuration from file `$filename` and returns its contents as an array.
     */
    public function parseFile(string $filename): array;

    /**
     * Parses a configuration from string `$config` and returns its contents as an array.
     */
    public function parseString(string $config): array;

    /**
     * Returns an array of allowed file extensions for this parser.
     *
     * @return list<string>
     */
    public static function getSupportedExtensions(): array;
}
