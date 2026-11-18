<?php
declare(strict_types=1);

namespace Arris\Core\Config;

use Arris\Core\Config\Exception\EmptyDirectoryException;
use Arris\Core\Config\Exception\FileNotFoundException;
use Arris\Core\Config\Exception\UnsupportedFormatException;
use Arris\Core\Config\Parser\ParserInterface;
use FilesystemIterator;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Read-only configuration loader.
 */
class Config extends AbstractConfig
{
    /**
     * Parsers supported by Config. An extension is resolved to the first parser
     * that declares it.
     *
     * @var list<class-string<ParserInterface>>
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
     * @param array $values Filename(s) or an empty array
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
     * Static loader for explicit files.
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
        $config = static::createEmpty();

        $config->data = KeyPath::mergeBase($config->getDefaults(), $data);

        return $config;
    }

    /**
     * Loads every supported file from a directory.
     *
     * @deprecated Explicit file lists are preferred. Directory scanning is kept
     *             for backwards compatibility; do not use it for new code.
     */
    public static function fromDirectory(
        string $directory,
        ?ParserInterface $parser = null,
        bool $ignoreNonExistentFiles = false
    ): static {
        if (!is_dir($directory)) {
            if ($ignoreNonExistentFiles) {
                return new static([], $parser, true);
            }

            throw new FileNotFoundException("Configuration directory: [$directory] cannot be found");
        }

        $resolver = static::createEmpty();
        $files = $resolver->resolveDirectory($directory, $parser);

        return new static($files, $parser, $ignoreNonExistentFiles);
    }

    /**
     * Creates an empty instance for late-static factories.
     */
    protected static function createEmpty(): static
    {
        return new static();
    }

    /**
     * Loads configuration from explicit files.
     *
     * @throws EmptyDirectoryException|FileNotFoundException|UnsupportedFormatException|InvalidArgumentException
     */
    protected function loadFromFile(array $path, ?ParserInterface $parser = null): array
    {
        $data = [];

        foreach ($this->resolveSources($path, $parser) as $source) {
            $data = KeyPath::mergeBase($data, $source['parser']->parseFile($source['path']));
        }

        return $data;
    }

    /**
     * Resolves all sources before any file is parsed.
     *
     * @return list<array{path: string, parser: ParserInterface}>
     *
     * @throws FileNotFoundException|UnsupportedFormatException|InvalidArgumentException
     */
    private function resolveSources(array $paths, ?ParserInterface $forcedParser): array
    {
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

            foreach ($this->resolvePath($realPath) as $file) {
                $parser = $forcedParser ?? $this->getParser($this->getExtension($file));
                $sources[] = ['path' => $file, 'parser' => $parser];
            }
        }

        return $sources;
    }

    /**
     * Resolves one explicit file path.
     *
     * @throws FileNotFoundException|InvalidArgumentException
     */
    private function resolvePath(string $path): array
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
            throw new InvalidArgumentException(
                "Configuration path: [$path] is a directory; use Config::fromDirectory()"
            );
        }

        if (file_exists($path)) {
            throw new FileNotFoundException("Configuration path: [$path] is not a file or directory");
        }

        if ($this->ignore_non_existent_files) {
            return [];
        }

        throw new FileNotFoundException("Configuration file: [$path] cannot be found");
    }

    /**
     * Resolves a directory into a sorted list of files.
     *
     * @return list<string>
     *
     * @throws EmptyDirectoryException|FileNotFoundException
     */
    private function resolveDirectory(string $directory, ?ParserInterface $parser): array
    {
        if (!is_readable($directory)) {
            throw new FileNotFoundException("Configuration directory: [$directory] is not readable");
        }

        try {
            $iterator = new FilesystemIterator($directory);
        } catch (UnexpectedValueException $exception) {
            throw new FileNotFoundException(
                "Configuration directory: [$directory] is not readable",
                0,
                $exception
            );
        }

        $allowedExtensions = $parser === null
            ? null
            : array_map('strtolower', $parser::getSupportedExtensions());

        $files = [];

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $filename = $fileInfo->getFilename();

            if ($filename === '' || $filename[0] === '.') {
                continue;
            }

            $file = $fileInfo->getPathname();

            if ($this->isIgnoredFile($file)) {
                continue;
            }

            if ($allowedExtensions !== null) {
                if (!in_array($this->getExtension($file), $allowedExtensions, true)) {
                    continue;
                }
            } elseif (!$this->supportsExtension($this->getExtension($file))) {
                continue;
            }

            $files[] = $file;
        }

        if ($files === []) {
            throw new EmptyDirectoryException("Configuration directory: [$directory] is empty");
        }

        sort($files);

        return $files;
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
            /** @var class-string<ParserInterface> $class */
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
}
