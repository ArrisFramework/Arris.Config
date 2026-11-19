<?php
declare(strict_types=1);

namespace Arris\Core\Config\Parser;

use Arris\Core\Config\Exception\InvalidConfigException;
use Arris\Core\Config\Exception\ParseException;

/**
 * JSON parser
 *
 * @package    Config
 * @license    MIT
 */
class Json implements ParserInterface
{
    public function __construct()
    {
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
        $data = json_decode($config, true);

        return (array)$this->parse($data, $filename);
    }

    /**
     * Completes parsing of JSON data.
     *
     * @throws ParseException|InvalidConfigException
     */
    protected function parse(mixed $data = null, ?string $filename = null): mixed
    {
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = function_exists('json_last_error_msg')
                ? json_last_error_msg()
                : 'Syntax error';

            throw new ParseException([
                'message' => $error_message,
                'code'    => json_last_error(),
                'file'    => $filename ?? '',
            ]);
        }

        if ($data !== null && !is_array($data)) {
            throw new InvalidConfigException('JSON configuration root must be an object or array');
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSupportedExtensions(): array
    {
        return ['json'];
    }
}
