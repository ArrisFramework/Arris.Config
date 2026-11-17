<?php

namespace Arris\Core\Config\Parser;

use Arris\Core\Config\Exception\ParseException;

/**
 * INI parser
 *
 * @package    Config
 * @license    MIT
 */
class Ini implements ParserInterface
{
    public function __construct()
    {
    }

    /**
     * {@inheritDoc}
     * Parses an INI file as an array
     *
     * @throws ParseException If there is an error parsing the INI file
     */
    public function parseFile($filename)
    {
        error_clear_last();
        $data = @parse_ini_file($filename, true);
        return $this->parse($data, $filename);
    }

    /**
     * {@inheritDoc}
     * Parses an INI string as an array
     *
     * @throws ParseException If there is an error parsing the INI string
     */
    public function parseString($config)
    {
        error_clear_last();
        $data = @parse_ini_string($config, true);
        return $this->parse($data);
    }

    /**
     * Completes parsing of INI data
     *
     * @param  array   $data
     * @param  string $filename
     *
     * @throws ParseException If there is an error parsing the INI data
     */
    protected function parse($data = null, $filename = null)
    {
        if (!$data) {
            $error = error_get_last();

            // Parse functions may return NULL but set no error if the string contains no parsable data
            if (!is_array($error)) {
                $error = ['message' => 'No parsable content in data.'];
            }

            $error['file'] = $filename;

            // If the string contains no parsable data, no error is set, resulting in any previous
            // error persisting in error_get_last().
            error_clear_last();

            throw new ParseException($error);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSupportedExtensions(): array
    {
        return ['ini'];
    }
}
