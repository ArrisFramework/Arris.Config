<?php
declare(strict_types=1);

namespace Arris\Core\Config\Exception;

use Arris\Core\Config\Exception;

/**
 * Thrown when parsed data is not a configuration tree (for example, a scalar
 * at the document root).
 */
class InvalidConfigException extends Exception
{
}
