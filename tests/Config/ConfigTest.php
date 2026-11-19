<?php
declare(strict_types=1);

namespace Tests\Config;

use Arris\Core\Config\Config;
use Arris\Core\Config\Exception\FileNotFoundException;
use Arris\Core\Config\Exception\UnsupportedFormatException;
use Arris\Core\Config\Parser\Json as JsonParser;
use Arris\Core\Config\Parser\Toml;
use Arris\Core\Config\Parser\Yaml;
use PHPUnit\Framework\TestCase;
use Tests\Config\Fixture\ConfigWithDefaults;

class ConfigTest extends TestCase
{
    /**
     * @covers \Arris\Core\Config\Config::load()
     * @covers \Arris\Core\Config\Config::loadFromFile()
     * @covers \Arris\Core\Config\Config::getParser()
     */
    public function testLoadWithUnsupportedFormat()
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('Unsupported configuration format');

        Config::load([__DIR__ . '/mocks/fail/error.lib']);
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     * @covers \Arris\Core\Config\Config::loadFromFile()
     * @covers \Arris\Core\Config\Config::getParser()
     */
    public function testConstructWithUnsupportedFormat()
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('Unsupported configuration format');

        new Config([__DIR__ . '/mocks/fail/error.lib']);
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     * @covers \Arris\Core\Config\Config::loadFromFile()
     * @covers \Arris\Core\Config\Config::resolveSources()
     */
    public function testConstructWithInvalidPath()
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('Configuration file: [ladadeedee] cannot be found');

        new Config(['ladadeedee']);
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     * @covers \Arris\Core\Config\Config::loadFromFile()
     */
    public function testConstructWithArray()
    {
        $paths = [__DIR__ . '/mocks/pass/config.json', __DIR__ . '/mocks/pass/config2.json'];
        $config = new Config($paths);

        $this->assertSame('localhost', $config->get('host'));
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     * @covers \Arris\Core\Config\Config::loadFromFile()
     */
    public function testConstructWithArrayWithNonexistentFile()
    {
        $this->expectException(FileNotFoundException::class);

        new Config([
            __DIR__ . '/mocks/pass/config.json',
            __DIR__ . '/mocks/pass/config3.json',
        ]);
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     */
    public function testConstructWithArrayWithOptionalFile()
    {
        $paths = [__DIR__ . '/mocks/pass/config.json', '?' . __DIR__ . '/mocks/pass/config2.json'];
        $config = new Config($paths);

        $this->assertSame('localhost', $config->get('host'));
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     */
    public function testConstructWithArrayWithOptionalNonexistentFile()
    {
        $paths = [__DIR__ . '/mocks/pass/config.json', '?' . __DIR__ . '/mocks/pass/config3.json'];
        $config = new Config($paths);

        $this->assertSame('localhost', $config->get('host'));
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     */
    public function testConstructWithYml()
    {
        $config = new Config([__DIR__ . '/mocks/pass/config.yml']);

        $this->assertSame('localhost', $config->get('host'));
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     */
    public function testConstructWithEmptyYml()
    {
        $config = new Config([__DIR__ . '/mocks/pass/empty.yaml']);

        $this->assertSame([], $config->all());
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     */
    public function testConstructWithFileParser()
    {
        $config = new Config([__DIR__ . '/mocks/pass/json.config'], new JsonParser());

        $this->assertSame('localhost', $config->get('host'));
    }

    /**
     * @covers \Arris\Core\Config\Config::fromFile()
     */
    public function testFromFileAcceptsSingleFile()
    {
        $config = Config::fromFile(__DIR__ . '/mocks/pass/config.json');

        $this->assertSame('localhost', $config->get('host'));
    }

    /**
     * @covers \Arris\Core\Config\Config::fromFile()
     */
    public function testFromFileIgnoresMissingFileWhenRequested()
    {
        $config = Config::fromFile(__DIR__ . '/mocks/pass/does-not-exist.json', null, true);

        $this->assertSame([], $config->all());
    }

    /**
     * @covers \Arris\Core\Config\Config::fromString()
     */
    public function testConstructWithStringParser()
    {
        $settings = file_get_contents(__DIR__ . '/mocks/pass/config.php');
        $config = Config::fromString($settings, new \Arris\Core\Config\Parser\Php());

        $this->assertSame('localhost', $config->get('host'));
    }

    /**
     * @covers \Arris\Core\Config\Config::fromString()
     */
    public function testFromStringWithYaml()
    {
        $config = Config::fromString("host: localhost\nport: 80\n", new Yaml());

        $this->assertSame('localhost', $config->get('host'));
        $this->assertSame(80, $config->get('port'));
    }

    /**
     * @covers \Arris\Core\Config\Config::fromString()
     */
    public function testFromStringWithToml()
    {
        $config = Config::fromString("host = \"localhost\"\nport = 80\n", new Toml());

        $this->assertSame('localhost', $config->get('host'));
        $this->assertSame(80, $config->get('port'));
    }

    /**
     * @covers \Arris\Core\Config\Config::fromString()
     */
    public function testFromStringMergesSubclassDefaults()
    {
        $config = ConfigWithDefaults::fromString('{"parsed":2}', new JsonParser());

        $this->assertSame(['default' => 1, 'n' => null, 'parsed' => 2], $config->all());
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     * @covers \Arris\Core\Config\Config::get()
     * @dataProvider specialConfigProvider
     */
    public function testGetReturnsArrayMergedArray($config, int $expectedCount)
    {
        $this->assertCount($expectedCount, $config->get('servers'));
    }

    /**
     * @covers \Arris\Core\Config\Config::loadFromFile()
     * @covers \Arris\Core\Config\Config::resolveSources()
     */
    public function testDottedKeysStayLiteral()
    {
        $config = new Config([__DIR__ . '/mocks/pass/config2.ini']);

        $this->assertSame('localhost', $config->get('network\\.http\\.host'));
        $this->assertSame('80', $config->get('network\\.http\\.port'));
        $this->assertSame(['host1', 'host2', 'host3'], $config->get('network\\.group\\.servers'));
        $this->assertNull($config->get('network.http.host'));

        // Sections are native INI nesting and still work.
        $this->assertSame('configuration', $config->get('application.name'));
    }

    /**
     * @covers \Arris\Core\Config\Config::fromString()
     */
    public function testEscapedDotIsLiteralKey()
    {
        $config = Config::fromString('{"a.b": 1}', new JsonParser());

        $this->assertSame(1, $config->get('a\\.b'));
        $this->assertNull($config->get('a.b'));
        $this->assertArrayHasKey('a.b', $config->all());
    }

    /**
     * @covers \Arris\Core\Config\Config::resolveSources()
     */
    public function testArrayPathMustContainStrings()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Config([['a']]);
    }

    /**
     * @covers \Arris\Core\Config\Config::resolveSources()
     */
    public function testNestedArrayPathIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Config([
            'custom' => ['ccc.yaml', 'ddd.yaml'],
        ]);
    }

    /**
     * @covers \Arris\Core\Config\Config::resolveSources()
     */
    public function testConstructorDoesNotLoadDirectories()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pass explicit files');

        new Config([__DIR__ . '/mocks/dir']);
    }

    /**
     * @covers \Arris\Core\Config\Config::resolveSources()
     * @covers \Arris\Core\Config\Config::getExtension()
     */
    public function testExplicitUppercaseExtensionIsSupported()
    {
        $file = sys_get_temp_dir() . '/arris_cfg_' . bin2hex(random_bytes(6)) . '.JSON';
        file_put_contents($file, '{"host":"localhost"}');

        try {
            $config = Config::fromFile($file);
            $this->assertSame('localhost', $config->get('host'));
        } finally {
            @unlink($file);
        }
    }

    /**
     * @covers \Arris\Core\Config\Config::getParser()
     */
    public function testFileWithoutExtensionReportsUnsupportedFormat()
    {
        $file = tempnam(sys_get_temp_dir(), 'arris_cfg_');
        file_put_contents($file, 'content');

        try {
            $this->expectException(UnsupportedFormatException::class);
            $this->expectExceptionMessage('Unsupported configuration format: (none)');

            new Config([$file]);
        } finally {
            @unlink($file);
        }
    }

    /**
     * @covers \Arris\Core\Config\Config::getParser()
     */
    public function testCustomParserCanBeForcedForExplicitFile()
    {
        $config = Config::fromFile(__DIR__ . '/mocks/pass/json.config', new JsonParser());

        $this->assertSame('localhost', $config->get('host'));
    }

    /**
     * @covers \Arris\Core\Config\Config::__construct()
     */
    public function testEmptyConfig()
    {
        $this->assertSame([], (new Config([]))->all());
    }

    /**
     * @covers \Arris\Core\Config\Config::resolveSources()
     */
    public function testGlobPatternIsNotFound()
    {
        $this->expectException(FileNotFoundException::class);

        Config::load([__DIR__ . '/mocks/pass/*.json']);
    }

    /**
     * @covers \Arris\Core\Config\Config::resolvePath()
     */
    public function testEmptyStringPathThrows()
    {
        $this->expectException(FileNotFoundException::class);

        Config::load(['']);
    }

    /**
     * @covers \Arris\Core\Config\Config::resolveSources()
     */
    public function testExplicitBackupFileHasNoSpecialMeaning()
    {
        $file = sys_get_temp_dir() . '/arris_cfg_' . bin2hex(random_bytes(6)) . '.json.bak';
        file_put_contents($file, '{"host":"localhost"}');

        try {
            $this->expectException(UnsupportedFormatException::class);

            new Config([$file]);
        } finally {
            @unlink($file);
        }
    }

    /**
     * @covers \Arris\Core\Config\KeyPath::mergeBase()
     */
    public function testRootListFilesReplaceInsteadOfMerge()
    {
        $dir = sys_get_temp_dir() . '/arris_cfg_' . bin2hex(random_bytes(6));
        mkdir($dir);
        file_put_contents($dir . '/one.php', '<?php return ["a", "b", "c"];');
        file_put_contents($dir . '/two.php', '<?php return ["x"];');

        try {
            $config = Config::load([$dir . '/one.php', $dir . '/two.php']);

            $this->assertSame(['x'], $config->all());
        } finally {
            unlink($dir . '/one.php');
            unlink($dir . '/two.php');
            rmdir($dir);
        }
    }

    /**
     * Provides example configuration files merged together.
     */
    public static function specialConfigProvider(): array
    {
        return [
            [
                new Config(
                    [
                        __DIR__ . '/mocks/pass/config2.json',
                        __DIR__ . '/mocks/pass/config.yaml'
                    ]
                ),
                3,
            ],
        ];
    }
}
