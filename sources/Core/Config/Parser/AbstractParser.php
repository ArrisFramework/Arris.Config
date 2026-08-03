<?php

namespace Arris\Core\Config\Parser;

/**
 * Abstract parser
 *
 * @package    Config
 * @license    MIT
 */
abstract class AbstractParser implements ParserInterface
{

    /**
     * String with configuration
     *
     * @var string
     */
    protected $config;

    /**
     * Sets the string with configuration
     *
     * @param string $config
     * @param string $filename
     *
     * @codeCoverageIgnore
     */
    public function __construct($config, $filename = null)
    {
        $this->config = $config;
    }
}
