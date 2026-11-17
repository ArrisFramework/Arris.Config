<?php

namespace Tests\Config\Parser;

use Arris\Core\Config\Parser\Toml;
use PHPUnit\Framework\TestCase;

class TomlTest extends TestCase
{
    private Toml $toml;

    protected function setUp(): void
    {
        $this->toml = new Toml();
    }

    public function testGetSupportedExtensions()
    {
        $this->assertSame(['toml'], $this->toml->getSupportedExtensions());
    }

    public function testLoadToml()
    {
        $file = $this->toml->parseFile(__DIR__ . '/../mocks/pass/config.toml');
        $string = $this->toml->parseString(file_get_contents(__DIR__ . '/../mocks/pass/config.toml'));

        $this->assertSame('localhost', $file['host']);
        $this->assertSame(80, $file['port']);
        $this->assertSame('configuration', $file['application']['name']);

        $this->assertSame('localhost', $string['host']);
        $this->assertSame(80, $string['port']);
    }

    public function testLoadInvalidToml()
    {
        $this->expectException(\Arris\Core\Config\Exception\ParseException::class);

        $this->toml->parseString('key = ');
    }
}
