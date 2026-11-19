<?php
declare(strict_types=1);

namespace Arris;

use Arris\Core\Config\AbstractConfig;
use Arris\Core\Config\Config;
use Arris\Core\Config\Exception\FileNotFoundException;
use Arris\Core\Config\Exception\UnsupportedFormatException;
use Arris\Core\Config\KeyPath;
use Arris\Core\Config\Parser\ParserInterface;

final class AppConfig extends AbstractConfig
{
    /**
     * @param array $files Explicit filename(s)
     * @param array $defaults Defaults; files are patches on top of them
     *
     * @throws UnsupportedFormatException
     * @throws FileNotFoundException
     */
    public function __construct(array $files = [], array $defaults = [])
    {
        $loaded = (new Config($files))->all();
        $this->setData(KeyPath::mergePatch($defaults, $loaded));
    }

    /**
     * Builds an AppConfig from a single file.
     *
     * @throws UnsupportedFormatException
     * @throws FileNotFoundException
     */
    public static function fromFile(string $file, array $defaults = []): self
    {
        return new self([$file], $defaults);
    }

    /**
     * Builds an AppConfig from a configuration string.
     * The parser is required; null in the string deletes a default key.
     */
    public static function fromString(
        string $configuration,
        ParserInterface $parser,
        array $defaults = []
    ): self {
        $config = new self();
        $config->setData(KeyPath::mergePatch($defaults, $parser->parseString($configuration)));

        return $config;
    }

    /**
     * Adds a configuration layer to the existing data.
     */
    public function add(array $config): static
    {
        $this->setData(KeyPath::mergePatch($this->data ?? [], $config));

        return $this;
    }
}
