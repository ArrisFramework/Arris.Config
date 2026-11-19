<?php
declare(strict_types=1);

namespace Tests\Config\Fixture;

use Arris\Core\Config\Config;

class ConfigWithDefaults extends Config
{
    protected function getDefaults(): array
    {
        return [
            'default' => 1,
            'n'       => null,
        ];
    }
}
