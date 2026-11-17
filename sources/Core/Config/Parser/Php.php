<?php

namespace Arris\Core\Config\Parser;

use Arris\Core\Config\Exception\ParseException;
use Arris\Core\Config\Exception\UnsupportedFormatException;

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
     * Loads a PHP file and gets its' contents as an array
     *
     * @throws ParseException             If the PHP file throws an exception
     * @throws UnsupportedFormatException If the PHP file does not return an array
     */
    public function parseFile($filename)
    {
        try {
            $data = require $filename;
            return (array)$this->parse($data, $filename);
        } catch (ParseException|UnsupportedFormatException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new ParseException(
                [
                    'message'   => 'PHP file threw an exception',
                    'exception' => $exception,
                ]
            );
        }
    }

    /**
     * {@inheritDoc}
     * Loads a PHP string and gets its' contents as an array
     *
     * @throws ParseException             If the PHP string throws an exception
     * @throws UnsupportedFormatException If the PHP string does not return an array
     */
    public function parseString($config)
    {
        // Handle PHP start tag
        $config = trim($config);
        if (substr($config, 0, 2) === '<?') {
            $config = '?>' . $config;
        }

        try {
            $data = $this->isolate($config);
            return (array)$this->parse($data);
        } catch (ParseException|UnsupportedFormatException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new ParseException(
                [
                    'message'   => 'PHP string threw an exception',
                    'exception' => $exception,
                ]
            );
        }
    }

    /**
     * Completes parsing of PHP data
     *
     * @param  array $data
     * @param  string $filename
     *
     * @return array|null
     * @throws UnsupportedFormatException
     */
    protected function parse($data = null, $filename = null)
    {
        // If we have a closure, run it and expect an array back
        if ($data instanceof \Closure) {
            $data = $data();
        }

        // Check for array, if It's anything else, throw an exception
        if (!is_array($data)) {
            throw new UnsupportedFormatException('PHP data does not return an array');
        }

        return $data;
    }

    /**
     * Runs PHP string in isolated method
     *
     * @param  string $EGsfKPdue7ahnMTy
     *
     * @return array
     */
    protected function isolate($EGsfKPdue7ahnMTy)
    {
        return eval($EGsfKPdue7ahnMTy);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSupportedExtensions(): array
    {
        return ['php'];
    }
}
