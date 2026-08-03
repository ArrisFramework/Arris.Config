<?php

namespace Arris\Core\Config\Writer;

/**
 * Ini Writer.
 */
class Ini extends AbstractWriter
{
    /**
     * {@inheritdoc}
     * Writes an array to a Ini string.
     */
    public function toString($config, bool $pretty = true)
    {
        return $this->toINI($config);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSupportedExtensions(): array
    {
        return ['ini'];
    }

    /**
     * Converts array to INI string.
     * @param array $arr    Array to be converted
     * @param array $parent Parent array
     *
     * @return string Converted array as INI
     *
     * @see https://stackoverflow.com/a/17317168/6523409/
     */
    protected function toINI(array $arr, array $parent = [])
    {
        $converted = '';

        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $sec = array_merge((array) $parent, (array) $k);
                $converted .= '['.implode('.', $sec).']'.PHP_EOL;
                $converted .= $this->toINI($v, $sec);
            } else {
                $converted .= $k.'='.$v.PHP_EOL;
            }
        }

        return $converted;
    }
}
