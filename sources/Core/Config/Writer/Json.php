<?php

namespace Arris\Core\Config\Writer;

use Arris\Core\Config\Exception\WriteException;

/**
 * JSON Writer.
 *
 * @package    Config
 * @license    MIT
 */
class Json extends AbstractWriter
{
    /**
     * {@inheritdoc}
     * Writes an array to a JSON file.
     */
    public function toFile($config, $filename)
    {
        $data = $this->toString($config);
        $success = @file_put_contents($filename, $data.PHP_EOL);
        if ($success === false) {
            throw new WriteException(['file' => $filename]);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     * Writes an array to a JSON string.
     */
    public function toString($config, bool $pretty = true)
    {
        return json_encode($config, $pretty ? (JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : 0);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSupportedExtensions()
    {
        return ['json'];
    }
}
