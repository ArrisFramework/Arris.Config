<?php
declare(strict_types=1);

namespace Arris\Core\Config\Exception;

use Arris\Core\Config\ErrorException;

class ParseException extends ErrorException
{
    /**
     * @param array{
     *     message?: string,
     *     code?: int,
     *     type?: int,
     *     file?: string,
     *     line?: int,
     *     exception?: \Throwable|null
     * } $error
     */
    public function __construct(array $error)
    {
        $message   = $error['message'] ?? 'There was an error parsing the file';
        $code      = (int)($error['code'] ?? 0);
        $severity  = (int)($error['type'] ?? E_ERROR);
        $filename  = (string)($error['file'] ?? '');
        $lineno    = (int)($error['line'] ?? 0);
        $exception = $error['exception'] ?? null;

        parent::__construct($message, $code, $severity, $filename, $lineno, $exception);
    }
}
