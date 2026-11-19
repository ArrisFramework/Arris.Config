<?php

namespace Tests\Config\Parser;

use Arris\Core\Config\Parser\Ini;
use PHPUnit\Framework\TestCase;

class IniTest extends TestCase
{
    /**
     * @var Ini
     */
    protected $ini;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->ini = new Ini();
    }

    /**
     * @covers \Arris\Core\Config\Parser\Ini::getSupportedExtensions()
     */
    public function testGetSupportedExtensions()
    {
        $expected = ['ini'];
        $actual   = $this->ini->getSupportedExtensions();
        $this->assertSame($expected, $actual);
    }

    /**
     * @covers \Arris\Core\Config\Parser\Ini::parseFile()
     * @covers \Arris\Core\Config\Parser\Ini::parse()
     * Tests the case where an INI string contains data but parse_ini_string
     * returns an empty result without a retrievable parse error.
     */
    public function testLoadInvalidIniGBH()
    {
        $this->expectException(\Arris\Core\Config\Exception\ParseException::class);
        $this->expectExceptionMessage('No parsable content');
        $this->ini->parseFile(__DIR__ . '/../mocks/fail/error2.ini');
    }

    /**
     * @covers \Arris\Core\Config\Parser\Ini::parseString()
     * @covers \Arris\Core\Config\Parser\Ini::parse()
     */
    public function testLoadInvalidIni()
    {
        $this->expectException(\Arris\Core\Config\Exception\ParseException::class);
        $this->expectExceptionMessage('syntax error');

        $this->ini->parseString(file_get_contents(__DIR__ . '/../mocks/fail/error.ini'));
    }

    /**
     * @covers \Arris\Core\Config\Parser\Ini::parseString()
     */
    public function testEmptyAndCommentOnlyIniReturnEmptyArray()
    {
        @trigger_error('stale warning from host', E_USER_WARNING);

        $this->assertSame([], $this->ini->parseString(''));
        $this->assertSame([], $this->ini->parseString("; comment\n# comment\n"));
    }

    /**
     * @covers \Arris\Core\Config\Parser\Ini::parseFile()
     * @covers \Arris\Core\Config\Parser\Ini::parseString()
     * @covers \Arris\Core\Config\Parser\Ini::parse()
     */
    public function testLoadIni()
    {
        $file = $this->ini->parseFile(__DIR__ . '/../mocks/pass/config.ini');
        $string = $this->ini->parseString(file_get_contents(__DIR__ . '/../mocks/pass/config.ini'));

        $this->assertSame('localhost', $file['host']);
        $this->assertSame('80', $file['port']);

        $this->assertSame('localhost', $string['host']);
        $this->assertSame('80', $string['port']);
    }

    /**
     * @covers \Arris\Core\Config\Parser\Ini::parseFile()
     * @covers \Arris\Core\Config\Parser\Ini::parseString()
     * @covers \Arris\Core\Config\Parser\Ini::parse()
     */
    public function testLoadIniWithDottedName()
    {
        $file = $this->ini->parseFile(__DIR__ . '/../mocks/pass/config2.ini');
        $string = $this->ini->parseString(file_get_contents(__DIR__ . '/../mocks/pass/config2.ini'));

        // The parser returns raw keys; expanding dots is Config's job.
        $this->assertSame('localhost', $file['network.http.host']);
        $this->assertSame('80', $file['network.http.port']);
        $this->assertSame(['host1', 'host2', 'host3'], $file['network.group.servers']);
        $this->assertSame('configuration', $file['application']['name']);

        $this->assertSame('localhost', $string['network.http.host']);
        $this->assertSame('80', $string['network.http.port']);
        $this->assertSame(['host1', 'host2', 'host3'], $string['network.group.servers']);
    }
}
