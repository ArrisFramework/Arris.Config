<?php
declare(strict_types=1);

namespace Arris\Core\Config;

use Arris\Core\Config\Exception\EmptyDirectoryException;
use Arris\Core\Config\Exception\FileNotFoundException;
use Arris\Core\Config\Exception\UnsupportedFormatException;
use Arris\Core\Config\Parser\ParserInterface;
use FilesystemIterator;
use InvalidArgumentException;

/**
 * Read-only configuration loader.
 */
class Config extends AbstractConfig
{
    /**
     * Parsers supported by Config. An extension is resolved to the first parser
     * that declares it.
     *
     * @var string[]
     */
    protected array $supportedParsers = [
        Parser\Php::class,
        Parser\Ini::class,
        Parser\Json::class,
        Parser\Yaml::class,
        Parser\Toml::class,
    ];

    /**
     * Ignore non-existent config files instead of throwing?
     */
    private bool $ignore_non_existent_files;

    /**
     * @var array<string, class-string<ParserInterface>>|null
     */
    private ?array $parserMap = null;

    /**
     * @param array $values Filename(s), directory, or an empty array
     * @param ParserInterface|null $parser Force this parser for every file
     * @param bool $ignoreNonExistentFiles Skip missing files instead of throwing
     *
     * @throws EmptyDirectoryException|FileNotFoundException|UnsupportedFormatException
     */
    public function __construct(
        array $values = [],
        ?ParserInterface $parser = null,
        bool $ignoreNonExistentFiles = false
    ) {
        $this->ignore_non_existent_files = $ignoreNonExistentFiles;
        parent::__construct($this->loadFromFile($values, $parser));
    }

    /**
     * Static loader for files and directories.
     *
     * @throws EmptyDirectoryException|FileNotFoundException|UnsupportedFormatException
     */
    public static function load(
        array $values = [],
        ?ParserInterface $parser = null,
        bool $ignoreNonExistentFiles = false
    ): static {
        return new static($values, $parser, $ignoreNonExistentFiles);
    }

    /**
     * Static loader for a single file.
     */
    public static function fromFile(
        string $file,
        ?ParserInterface $parser = null,
        bool $ignoreNonExistentFiles = false
    ): static {
        return new static([$file], $parser, $ignoreNonExistentFiles);
    }

    /**
     * Static loader for a configuration string. The parser is required.
     */
    public static function fromString(string $configuration, ParserInterface $parser): static
    {
        $data = $parser->parseString($configuration);
        $config = new static();

        $config->data = KeyPath::mergeBase($config->getDefaults(), $data);
        $config->cache = [];

        return $config;
    }

    /**
     * Loads configuration from files or directories.
     *
     * @throws EmptyDirectoryException|FileNotFoundException|UnsupportedFormatException
     */
    protected function loadFromFile(array $path, ?ParserInterface $parser = null): array
    {
        $data = [];

        foreach ($this->resolveSources($path, $parser, $parser === null) as $source) {
            $data = KeyPath::mergeBase($data, $source['parser']->parseFile($source['path']));
        }

        return $data;
    }

    /**
     * Gets a parser for a given file extension.
     *
     * @throws UnsupportedFormatException If `$extension` is an unsupported file format
     */
    protected function getParser(string $extension): ParserInterface
    {
        $extension = strtolower($extension);
        $class = $this->parserMap()[$extension] ?? null;

        if ($class === null) {
            throw new UnsupportedFormatException('Unsupported configuration format: ' . $extension);
        }

        return new $class();
    }

    /**
     * Whether a parser exists for the given extension.
     */
    protected function supportsExtension(string $extension): bool
    {
        return isset($this->parserMap()[strtolower($extension)]);
    }

    /**
     * Builds an extension => parser class map once per Config instance.
     *
     * @return array<string, class-string<ParserInterface>>
     */
    private function parserMap(): array
    {
        if ($this->parserMap !== null) {
            return $this->parserMap;
        }

        $this->parserMap = [];

        foreach ($this->supportedParsers as $class) {
            foreach ($class::getSupportedExtensions() as $extension) {
                $this->parserMap[strtolower($extension)] ??= $class;
            }
        }

        return $this->parserMap;
    }

    /**
     * Returns a normalized file extension.
     */
    private function getExtension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Files ending with .bak are backups and are skipped during directory scans.
     */
    private function isIgnoredFile(string $path): bool
    {
        return str_ends_with(strtolower($path), '.bak');
    }

    /**
     * Resolves all sources before any file is parsed.
     *
     * @return list<array{path: string, parser: ParserInterface}>
     *
     * @throws EmptyDirectoryException|FileNotFoundException|UnsupportedFormatException|InvalidArgumentException
     */
    private function resolveSources(
        array $paths,
        ?ParserInterface $forcedParser,
        bool $filterBySupported
    ): array {
        $sources = [];

        foreach ($paths as $entry) {
            if (!is_string($entry)) {
                throw new InvalidArgumentException('Configuration path must be a string');
            }

            $optional = str_starts_with($entry, '?');
            $realPath = $optional ? substr($entry, 1) : $entry;

            if ($optional && !file_exists($realPath)) {
                continue;
            }

            foreach ($this->resolvePath($realPath, $filterBySupported) as $file) {
                $parser = $forcedParser ?? $this->getParser($this->getExtension($file));
                $sources[] = ['path' => $file, 'parser' => $parser];
            }
        }

        return $sources;
    }

    /**
     * Resolves one path (file or directory) into a list of files.
     *
     * @throws EmptyDirectoryException If `$path` is an empty directory
     * @throws FileNotFoundException If the path does not exist or is not readable
     */
    private function resolvePath(string $path, bool $filterBySupported): array
    {
        if ($path === '') {
            throw new FileNotFoundException('Configuration file: [] cannot be found');
        }

        if (is_file($path)) {
            if (!is_readable($path)) {
                throw new FileNotFoundException("Configuration file: [$path] is not readable");
            }

            return [$path];
        }

        if (is_dir($path)) {
            $files = [];

            foreach (new FilesystemIterator($path) as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }

                $filename = $fileInfo->getFilename();

                // Keep the old behavior: hidden files are not config sources.
                if ($filename === '' || $filename[0] === '.') {
                    continue;
                }

                $file = $fileInfo->getPathname();

                if ($this->isIgnoredFile($file)) {
                    continue;
                }

                if ($filterBySupported && !$this->supportsExtension($this->getExtension($file))) {
                    continue;
                }

                $files[] = $file;
            }

            if ($files === []) {
                throw new EmptyDirectoryException("Configuration directory: [$path] is empty");
            }

            sort($files);

            return $files;
        }

        if (file_exists($path)) {
            return [$path];
        }

        if ($this->ignore_non_existent_files) {
            return [];
        }

        throw new FileNotFoundException("Configuration file: [$path] cannot be found");
    }
}
