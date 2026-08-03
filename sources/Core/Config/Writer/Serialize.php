<?php

namespace Arris\Core\Config\Writer;

/**
 * Class Serialize
 *
 * @package Config
 */
class Serialize extends AbstractWriter
{

    /**
     * {@inheritdoc}
     */
    public function toString($config, bool $pretty = true)
    {
        return serialize($config);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSupportedExtensions()
    {
        return ['txt'];
    }
}
