<?php
declare(strict_types=1);

namespace Arris\Core\Config\Exception;

use Arris\Core\Config\Exception;

/**
 * Thrown when set() would change the kind of an existing config node
 * (scalar to array, array to scalar, assoc to list, etc.).
 */
class StructureConflictException extends Exception
{
}
