<?php

namespace Arris\Core\Config\Writer;

use Symfony\Component\Yaml\Yaml as YamlParser;

/**
 * Yaml Writer.
 *
 * @package    Config
 * @license    MIT
 */
class Yaml extends AbstractWriter
{
    /**
     * {@inheritdoc}
     * Writes an array to a Yaml string.
     */
    public function toString($config, bool $pretty = true)
    {
        return YamlParser::dump($config);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSupportedExtensions()
    {
        return ['yaml'];
    }
}
