<?php
declare(strict_types=1);

namespace Arris\Core\Config\Parser;

use Arris\Core\Config\Exception\ParseException;

/**
 * INI parser.
 */
class Ini implements ParserInterface
{
    public function __construct()
    {
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
        $withoutComments = preg_replace('/^\s*[;#].*$/m', '', (string)$config);

        if (trim((string)$withoutComments) === '') {
            return [];
        }

        error_clear_last();
        $data = @parse_ini_string((string)$config, true);

        if ($data === false || $data === []) {
            $error = error_get_last();

            if (!is_array($error)) {
                $error = ['message' => 'No parsable content in data.'];
            }

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
