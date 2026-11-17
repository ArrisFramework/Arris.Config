<?php
declare(strict_types=1);

namespace Arris\Core\Config\Exception;

use Arris\Core\Config\Exception;

/**
 * Thrown when set() receives null as a value. Use remove() to delete a key
 * or replace() to store null.
 */
class InvalidValueException extends Exception
{
}
