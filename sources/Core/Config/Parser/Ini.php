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
     */
    public function parseString(string $config, ?string $filename = null): array
    {
        $withoutComments = preg_replace('/^\s*[;#].*$/m', '', $config);

        if (trim((string)$withoutComments) === '') {
            return [];
        }

        error_clear_last();
        $data = @parse_ini_string($config, true);

        if ($data === false || $data === []) {
            $error = error_get_last();

            if (!is_array($error)) {
                $error = ['message' => 'No parsable content in data.'];
            }

            $error['type'] = E_ERROR;
            $error['file'] = $filename ?? '';
            $error['line'] = 0;

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
