<?php

namespace Arris\Core\Config\Parser;

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
     * Parses an JSON file as an array
     *
     * @throws ParseException If there is an error parsing the JSON file
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
     * Parses an JSON string as an array
     *
     * @throws ParseException If there is an error parsing the JSON string
     */
    public function parseString($config)
    {
        $data = json_decode($config, true);

        return (array)$this->parse($data);
    }

    /**
     * Completes parsing of JSON data
     *
     * @param  array  $data
     * @param  string $filename
     * @return array|null
     *
     * @throws ParseException If there is an error parsing the JSON data
     */
    protected function parse($data = null, $filename = null)
    {
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message  = 'Syntax error';
            if (function_exists('json_last_error_msg')) {
                $error_message = json_last_error_msg();
            }

            $error = [
                'message' => $error_message,
                'type'    => json_last_error(),
                'file'    => $filename,
            ];

            throw new ParseException($error);
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
