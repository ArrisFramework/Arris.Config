<?php
declare(strict_types=1);

namespace Arris\Core\Config\Parser;

use Arris\Core\Config\Exception\InvalidConfigException;
use Arris\Core\Config\Exception\ParseException;
use Arris\Core\Config\Exception\UnsupportedFormatException;
use Symfony\Component\Yaml\Yaml as YamlParser;
use Throwable;

/**
 * YAML parser.
 */
class Yaml implements ParserInterface
{
    /**
     * @throws UnsupportedFormatException
     */
    public function __construct()
    {
        if (!class_exists(YamlParser::class)) {
            throw new UnsupportedFormatException('YAML support requires the symfony/yaml package');
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseException|InvalidConfigException
     */
    public function parseFile(string $filename): array
    {
        $contents = @file_get_contents($filename);

        if ($contents === false) {
            throw new ParseException([
                'message' => 'Unable to read configuration file',
                'file'    => $filename,
            ]);
        }

        return $this->parseString($contents, $filename);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseException|InvalidConfigException
     */
    public function parseString(string $config, ?string $filename = null): array
    {
        $isBlank = trim($config) === '';

        try {
            $data = YamlParser::parse($config, YamlParser::PARSE_CONSTANT);
        } catch (Throwable $exception) {
            throw new ParseException([
                'message'   => 'Error parsing YAML string',
                'file'      => $filename ?? '',
                'exception' => $exception,
            ]);
        }

        if ($data === null) {
            if ($isBlank) {
                return [];
            }

            throw new InvalidConfigException('YAML configuration root must be an object or array');
        }

        if (!is_array($data)) {
            throw new InvalidConfigException('YAML configuration root must be an object or array');
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSupportedExtensions(): array
    {
        return ['yaml', 'yml'];
    }
}
