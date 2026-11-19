<?php
declare(strict_types=1);

namespace Arris\Core\Config;

/**
 * Marker interface implemented by every exception thrown by this package.
 *
 * Allows a single catch for both regular config errors and parse errors:
 *
 *     catch (\Arris\Core\Config\ConfigExceptionInterface $exception) { ... }
 */
interface ConfigExceptionInterface
{
}
