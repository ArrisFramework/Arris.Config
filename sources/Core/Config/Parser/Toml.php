<?php
declare(strict_types=1);

namespace Arris\Core\Config\Parser;

use Arris\Core\Config\Exception\ParseException;
use Arris\Core\Config\Exception\UnsupportedFormatException;
use Internal\Toml\Toml as TomlParser;
use Throwable;

/**
 * TOML parser (requires internal/toml).
 */
class Toml implements ParserInterface
{
    /**
     * @throws UnsupportedFormatException
     */
    public function __construct()
    {
        if (!class_exists(TomlParser::class)) {
            throw new UnsupportedFormatException('TOML support requires the internal/toml package');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function parseFile($filename)
    {
        $contents = @file_get_contents($filename);

        if ($contents === false) {
            throw new ParseException([
                'message' => 'Unable to read configuration file',
                'file'    => $filename,
            ]);
        }

        return $this->parseString($contents);
    }

    /**
     * {@inheritDoc}
     */
    public function parseString($config)
    {
        try {
            return TomlParser::parseToArray($config);
        } catch (Throwable $exception) {
            throw new ParseException([
                'message'   => 'Error parsing TOML string',
                'exception' => $exception,
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getSupportedExtensions(): array
    {
        return ['toml'];
    }
}
