<?php
declare(strict_types=1);

namespace Arris\Core\Config\Parser;

use Arris\Core\Config\Exception\ParseException;
use Arris\Core\Config\Exception\UnsupportedFormatException;
use Symfony\Component\Yaml\Yaml as YamlParser;
use Throwable;

/**
 * YAML parser.
 */
class Yaml implements ParserInterface
{
    public function __construct()
    {
        if (!class_exists(YamlParser::class)) {
            throw new UnsupportedFormatException('YAML support requires the symfony/yaml package');
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
            $data = YamlParser::parse($config, YamlParser::PARSE_CONSTANT);
        } catch (Throwable $exception) {
            throw new ParseException([
                'message'   => 'Error parsing YAML string',
                'exception' => $exception,
            ]);
        }

        return (array)$data;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSupportedExtensions(): array
    {
        return ['yaml', 'yml'];
    }
}
