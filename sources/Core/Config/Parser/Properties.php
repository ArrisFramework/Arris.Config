<?php

namespace Arris\Core\Config\Parser;

/**
 * Properties parser.
 *
 * @package    Config
 * @license    MIT
 */
class Properties implements ParserInterface
{
    /**
     * {@inheritdoc}
     * Parses a Properties file as an array.
     */
    public function parseFile($filename)
    {
        return $this->parse(file_get_contents($filename));
    }

    /**
     * {@inheritdoc}
     * Parses a Properties string as an array.
     */
    public function parseString($config)
    {
        return $this->parse($config);
    }

    private function parse($txtProperties)
    {
        $result = [];

        // first remove all escaped whitespace characters:
        $txtProperties = preg_replace('/(?<!\\\\)\\\\[\r\n\t\f\v][ \r]*/', '', $txtProperties);

        // next split all lines not starting with # or ! on characters = or : (unless escaped):
        preg_match_all('/^([^#!].*)(?<!\\\\)[=:](.*)$/mU', $txtProperties, $matches, PREG_SET_ORDER, 0);

        foreach ($matches as $match) {
            $result[trim(stripslashes($match[1]))] = trim(stripslashes($match[2]));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSupportedExtensions(): array
    {
        return ['properties'];
    }
}
