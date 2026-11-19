<?php
declare(strict_types=1);

namespace Tests;

use Arris\AppConfig;
use Arris\Core\Config\Parser\Json as JsonParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppConfig::class)]
class AppConfigTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/arris_test_' . bin2hex(random_bytes(8));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    }

    #[Test]
    public function constructorMergesDefaultsAndFileData(): void
    {
        // Создаем мок конфига
        $configFile = $this->tempDir . '/test.php';
        file_put_contents($configFile, '<?php return ["db" => ["host" => "prod", "user" => "admin"]];');

        $defaults = [
            'db' => ['host' => 'localhost', 'port' => 3306, 'user' => 'root'],
            'app' => ['debug' => true]
        ];

        $config = new AppConfig([$configFile], $defaults);

        // Файл перезаписал дефолты
        $this->assertEquals('prod', $config->get('db.host'));
        $this->assertEquals('admin', $config->get('db.user'));

        // Дефолты сохранились для отсутствующих ключей
        $this->assertEquals(3306, $config->get('db.port'));

        // Незатронутые ветки дефолтов
        $this->assertTrue($config->get('app.debug'));
    }

    #[Test]
    public function constructorHandlesEmptyFilesArray(): void
    {
        $defaults = ['key' => 'value'];
        $config = new AppConfig([], $defaults);

        $this->assertEquals('value', $config->get('key'));
    }

    #[Test]
    public function constructorHandlesMissingOptionalFiles(): void
    {
        // Префикс "?" должен игнорировать отсутствие файла
        // (если hassankhan/config поддерживает эту фичу)
        $defaults = ['fallback' => 'ok'];

        $config = new AppConfig(['?' . $this->tempDir . '/missing.php'], $defaults);

        $this->assertEquals('ok', $config->get('fallback'));
    }

    #[Test]
    public function eachInstanceHasIndependentData(): void
    {
        $config1 = new AppConfig([], ['app' => ['id' => 1]]);
        $config2 = new AppConfig([], ['app' => ['id' => 2]]);

        $this->assertEquals(1, $config1->get('app.id'));
        $this->assertEquals(2, $config2->get('app.id'));

        // Изменение одного не влияет на другой
        $config1->set('app.id', 999);
        $this->assertEquals(999, $config1->get('app.id'));
        $this->assertEquals(2, $config2->get('app.id')); // Не изменился!
    }

    #[Test]
    public function addIsVisibleAfterRead(): void
    {
        $config = new AppConfig([], ['a' => 1]);
        $this->assertSame(1, $config->get('a'));

        $config->add(['a' => 2]);

        $this->assertSame(2, $config->get('a'));
    }

    #[Test]
    public function addKeepsDottedKeysLiteral(): void
    {
        $config = new AppConfig([]);
        $config->add(['a.b' => 1]);

        $this->assertSame(1, $config->get('a\\.b'));
        $this->assertNull($config->get('a.b'));
    }

    #[Test]
    public function defaultsKeepDottedKeysLiteral(): void
    {
        $config = new AppConfig([], ['a.b' => 1]);

        $this->assertSame(1, $config->get('a\\.b'));
        $this->assertNull($config->get('a.b'));
    }

    #[Test]
    public function replaceKeepsDottedKeysLiteral(): void
    {
        $config = new AppConfig([], ['old' => true]);
        $config->replace(['a.b' => 1]);

        $this->assertNull($config->get('old'));
        $this->assertSame(1, $config->get('a\\.b'));
        $this->assertNull($config->get('a.b'));
    }

    #[Test]
    public function escapedDotInFileStaysLiteral(): void
    {
        $configFile = $this->tempDir . '/dots.json';
        file_put_contents($configFile, '{"a.b": 1}');

        $config = new AppConfig([$configFile]);

        $this->assertSame(1, $config->get('a\\.b'));
        $this->assertNull($config->get('a.b'));
    }

    #[Test]
    public function defaultsDeepMergeWithFile(): void
    {
        $configFile = $this->tempDir . '/app.json';
        file_put_contents($configFile, '{"application":{"name":"custom"}}');

        $config = new AppConfig(
            [$configFile],
            ['application' => ['name' => 'default', 'secret' => 's3cr3t']]
        );

        $this->assertSame('custom', $config->get('application.name'));
        $this->assertSame('s3cr3t', $config->get('application.secret'));
    }

    #[Test]
    public function filesReplaceDefaultLists(): void
    {
        $configFile = $this->tempDir . '/servers.json';
        file_put_contents($configFile, '{"servers":["h4"]}');

        $config = new AppConfig(
            [$configFile],
            ['servers' => ['h1', 'h2', 'h3']]
        );

        $this->assertSame(['h4'], $config->get('servers'));
    }

    #[Test]
    public function defaultsPreserveNullValues(): void
    {
        $config = new AppConfig([], ['upload' => ['host' => null, 'port' => 25]]);

        $this->assertTrue($config->has('upload.host'));
        $this->assertNull($config->get('upload.host'));
        $this->assertSame(25, $config->get('upload.port'));
    }

    #[Test]
    public function fileNullDeletesDefault(): void
    {
        $configFile = $this->tempDir . '/nulls.json';
        file_put_contents($configFile, '{"database":{"host":null}}');

        $config = new AppConfig(
            [$configFile],
            ['database' => ['host' => 'localhost', 'port' => 3306]]
        );

        $this->assertFalse($config->has('database.host'));
        $this->assertSame(3306, $config->get('database.port'));
    }

    #[Test]
    public function fileCanChangeScalarDefaultToArray(): void
    {
        $configFile = $this->tempDir . '/redis.json';
        file_put_contents($configFile, '{"redis":{"enabled":true}}');

        $config = new AppConfig([$configFile], ['redis' => false]);

        $this->assertSame(['enabled' => true], $config->get('redis'));
    }

    #[Test]
    public function fileNullDeletesAppDefaults(): void
    {
        $configFile = $this->tempDir . '/delete.json';
        file_put_contents($configFile, '{"a":null}');

        $config = new AppConfig([$configFile], ['a' => 1, 'b' => 2]);

        $this->assertFalse($config->has('a'));
        $this->assertSame(2, $config->get('b'));
    }

    #[Test]
    public function fileNullWithoutDefaultIsDropped(): void
    {
        $configFile = $this->tempDir . '/null-without-default.json';
        file_put_contents($configFile, '{"a":null,"b":1}');

        $config = new AppConfig([$configFile]);

        $this->assertFalse($config->has('a'));
        $this->assertSame(1, $config->get('b'));
    }

    #[Test]
    public function fromFileAcceptsSingleFile(): void
    {
        $configFile = $this->tempDir . '/single.json';
        file_put_contents($configFile, '{"a":1}');

        $config = AppConfig::fromFile($configFile);

        $this->assertSame(1, $config->get('a'));
    }

    #[Test]
    public function fromStringParsesAndMergesDefaults(): void
    {
        $config = AppConfig::fromString(
            '{"a":1,"b":2}',
            new JsonParser(),
            ['a' => 0, 'c' => 3]
        );

        $this->assertSame(1, $config->get('a'));
        $this->assertSame(2, $config->get('b'));
        $this->assertSame(3, $config->get('c'));
    }

    #[Test]
    public function fromStringNullDeletesDefault(): void
    {
        $config = AppConfig::fromString(
            '{"a":null}',
            new JsonParser(),
            ['a' => 1, 'b' => 2]
        );

        $this->assertFalse($config->has('a'));
        $this->assertSame(2, $config->get('b'));
    }

    #[Test]
    public function fromStringReplacesLists(): void
    {
        $config = AppConfig::fromString(
            '{"servers":["h4"]}',
            new JsonParser(),
            ['servers' => ['h1', 'h2', 'h3']]
        );

        $this->assertSame(['h4'], $config->get('servers'));
    }

    #[Test]
    public function allRoundTripIsExactOnlyThroughReplace(): void
    {
        $source = new AppConfig([], [
            'a' => 1,
            'n' => null,
            'lit.x' => 2,
        ]);

        $all = $source->all();

        $replaced = new AppConfig([], []);
        $replaced->replace($all);

        $added = new AppConfig([], []);
        $added->add($all);

        $this->assertSame($all, $replaced->all());

        $this->assertSame(1, $added->get('a'));
        $this->assertFalse($added->has('n'));
        $this->assertSame(2, $added->get('lit\\.x'));
        $this->assertNull($added->get('lit.x'));
    }

    #[Test]
    public function appConfigIsFinal(): void
    {
        $this->assertTrue((new \ReflectionClass(AppConfig::class))->isFinal());
    }
}