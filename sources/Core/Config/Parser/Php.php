<?php
declare(strict_types=1);

namespace Arris\Core\Config\Parser;

use Arris\Core\Config\Exception\InvalidConfigException;
use Arris\Core\Config\Exception\ParseException;

/**
 * PHP parser
 *
 * @package    Config
 * @license    MIT
 */
class Php implements ParserInterface
{
    public function __construct()
    {
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseException|InvalidConfigException
     */
    public function parseFile(string $filename): array
    {
        try {
            $data = require $filename;

            return $this->parse($data, $filename);
        } catch (ParseException|InvalidConfigException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new ParseException([
                'message'   => 'PHP file threw an exception',
                'file'      => $filename,
                'exception' => $exception,
            ]);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws ParseException|InvalidConfigException
     */
    public function parseString(string $config, ?string $filename = null): array
    {
        // Handle PHP start tag
        $config = trim($config);
        if (substr($config, 0, 2) === '<?') {
            $config = '?>' . $config;
        }

        try {
            $data = $this->isolate($config);

            return $this->parse($data, $filename);
        } catch (ParseException|InvalidConfigException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new ParseException([
                'message'   => 'PHP string threw an exception',
                'file'      => $filename ?? '',
                'exception' => $exception,
            ]);
        }
    }

    /**
     * Completes parsing of PHP data.
     *
     * @throws InvalidConfigException
     */
    protected function parse(mixed $data = null, ?string $filename = null): array
    {
        // If we have a closure, run it and expect an array back
        if ($data instanceof \Closure) {
            $data = $data();
        }

        // Check for array; anything else is not a configuration tree
        if (!is_array($data)) {
            throw new InvalidConfigException('PHP data does not return an array');
        }

        return $data;
    }

    /**
     * Runs PHP code in an isolated scope.
     */
    protected function isolate(string $code): mixed
    {
        return eval($code);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSupportedExtensions(): array
    {
        return ['php'];
    }
}
