<?php
declare(strict_types=1);

namespace Arris;

use Arris\Core\Config\AbstractConfig;
use Arris\Core\Config\Config;
use Arris\Core\Config\Exception\EmptyDirectoryException;
use Arris\Core\Config\Exception\FileNotFoundException;
use Arris\Core\Config\Exception\UnsupportedFormatException;
use Arris\Core\Config\KeyPath;
use Arris\Core\Config\Parser\ParserInterface;

class AppConfig extends AbstractConfig
{
    /**
     * @param string|array $files Filename(s) or a directory
     * @param array $defaults Defaults; files are patches on top of them
     *
     * @throws UnsupportedFormatException
     * @throws FileNotFoundException
     * @throws EmptyDirectoryException
     */
    public function __construct(string|array $files = [], array $defaults = [])
    {
        $loaded = (new Config($files))->all();
        $this->data = KeyPath::mergePatch($defaults, $loaded);
        $this->cache = [];
    }

    /**
     * Builds an AppConfig from a configuration string.
     * The parser is required; null in the string deletes a default key.
     */
    public static function fromString(
        string $configuration,
        ParserInterface $parser,
        array $defaults = []
    ): static {
        $config = new static();
        $config->data = KeyPath::mergePatch($defaults, $parser->parseString($configuration));
        $config->cache = [];

        return $config;
    }

    /**
     * Adds a configuration layer to the existing data.
     */
    public function add(array $config): self
    {
        $this->data = KeyPath::mergePatch($this->data ?? [], $config);
        $this->cache = [];

        return $this;
    }
}
