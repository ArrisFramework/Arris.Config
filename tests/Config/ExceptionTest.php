<?php
declare(strict_types=1);

namespace Tests\Config;

use Arris\Core\Config\ConfigExceptionInterface;
use Arris\Core\Config\ErrorException;
use Arris\Core\Config\Exception;
use Arris\Core\Config\Exception\ParseException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testAllConfigExceptionsShareMarker(): void
    {
        $this->assertInstanceOf(ConfigExceptionInterface::class, new Exception('config error'));
        $this->assertInstanceOf(ConfigExceptionInterface::class, new ErrorException('config error'));
        $this->assertInstanceOf(ConfigExceptionInterface::class, new ParseException([]));
        $this->assertInstanceOf(
            ConfigExceptionInterface::class,
            new \Arris\Core\Config\Exception\StructureConflictException('config error')
        );
    }

    public function testParseExceptionKeepsDiagnostics(): void
    {
        $previous = new \RuntimeException('boom');

        $exception = new ParseException([
            'message'   => 'bad config',
            'code'      => 7,
            'file'      => 'config.json',
            'line'      => 3,
            'exception' => $previous,
        ]);

        $this->assertSame('bad config', $exception->getMessage());
        $this->assertSame(7, $exception->getCode());
        $this->assertSame('config.json', $exception->getFile());
        $this->assertSame(3, $exception->getLine());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
