<?php

namespace Tests\Config;

use Tests\Config\Fixture\SimpleConfig;
use PHPUnit\Framework\TestCase;

class AbstractConfigTest extends TestCase
{
    /**
     * @var
     */
    protected $config;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->config = new SimpleConfig(
            [
                'host' => 'localhost',
                'port'    => 80,
                'servers' => [
                    'host1',
                    'host2',
                    'host3'
                ],
                'application' => [
                    'name'   => 'configuration',
                    'secret' => 's3cr3t',
                    'runtime' => null,
                ],
                'user' => null,
            ]
        );
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::__construct()
     * @covers \Arris\Core\Config\AbstractConfig::getDefaults()
     */
    public function testDefaultOptionsSetOnInstantiation()
    {
        $config = new SimpleConfig(
            [
                'host' => 'localhost',
                'port'    => 80,
            ]
        );
        $this->assertSame('localhost', $config->get('host'));
        $this->assertSame(80, $config->get('port'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testGet()
    {
        $this->assertSame('localhost', $this->config->get('host'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testGetWithDefaultValue()
    {
        $this->assertSame(128, $this->config->get('ttl', 128));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testGetNestedKey()
    {
        $this->assertSame('configuration', $this->config->get('application.name'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testGetNestedKeyWithDefaultValue()
    {
        $this->assertSame(128, $this->config->get('application.ttl', 128));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testGetNonexistentKey()
    {
        $this->assertNull($this->config->get('proxy'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testGetNonexistentNestedKey()
    {
        $this->assertNull($this->config->get('proxy.name'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testGetReturnsArray()
    {
        $this->assertArrayHasKey('name', $this->config->get('application'));
        $this->assertSame('configuration', $this->config->get('application.name'));
        $this->assertCount(3, $this->config->get('application'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSet()
    {
        $this->config->set('region', 'apac');
        $this->assertSame('apac', $this->config->get('region'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetNestedKey()
    {
        $this->config->set('location.country', 'Singapore');
        $this->assertSame('Singapore', $this->config->get('location.country'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetArray()
    {
        $this->config->set('database', [
            'host' => 'localhost',
            'name' => 'mydatabase'
        ]);
        $this->assertIsArray($this->config->get('database'));
        $this->assertSame('localhost', $this->config->get('database.host'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetNestedArrayReflectsInParent()
    {
        $this->config->set('database', [
            'host' => 'localhost',
            'name' => 'mydatabase'
        ]);
        $this->assertIsArray($this->config->get('database'));
        $this->config->set('database.host', '127.0.0.1');
        $expected = [
            'host' => '127.0.0.1',
            'name' => 'mydatabase'
        ];
        $this->assertSame($expected, $this->config->get('database'));

        $this->config->set('config', [
            'database' => [
                'host' => 'localhost',
                'name' => 'mydatabase'
            ]
        ]);
        $this->config->get('config');
        $this->config->get('config.database');

        $this->config->set('config.database.host', '127.0.0.1');
        $expected = [
            'database' => [
                'host' => '127.0.0.1',
                'name' => 'mydatabase'
            ]
        ];
        $this->assertSame($expected, $this->config->get('config'));

        $expected = [
            'host' => '127.0.0.1',
            'name' => 'mydatabase'
        ];
        $this->assertSame($expected, $this->config->get('config.database'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetWithNestedMiddleArray()
    {
        $this->config->set('config', [
          'database' => [
              'host' => 'localhost',
              'name' => 'mydatabase'
          ]
        ]);
        $this->config->get('config');
        $this->config->get('config.database');
        $this->config->get('config.database.host');
        $this->config->get('config.database.name');

        $this->config->set('config.database', [
          'host' => '127.0.0.1',
          'name' => 'mynewdatabase'
        ]);
        $this->assertSame('127.0.0.1', $this->config->get('config.database.host'));
        $this->assertSame('mynewdatabase', $this->config->get('config.database.name'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     * @covers \Arris\Core\Config\AbstractConfig::remove()
     * @covers \Arris\Core\Config\AbstractConfig::replace()
     */
    public function testRemoveAndReplaceNull()
    {
        $this->config->set('database', [
            'host' => 'localhost',
            'name' => 'mydatabase'
        ]);
        $this->assertIsArray($this->config->get('database'));
        $this->assertSame('localhost', $this->config->get('database.host'));

        $this->config->remove('database.host');
        $this->assertFalse($this->config->has('database.host'));
        $this->assertNull($this->config->get('database.host'));

        $this->config->replace('database', null);
        $this->assertTrue($this->config->has('database'));
        $this->assertNull($this->config->get('database'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::has()
     */
    public function testHas()
    {
        $this->assertTrue($this->config->has('application'));
        $this->assertTrue($this->config->has('user'));
        $this->assertFalse($this->config->has('not_exist'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::has()
     */
    public function testHasNestedKey()
    {
        $this->assertTrue($this->config->has('application.name'));
        $this->assertTrue($this->config->has('application.runtime'));
        $this->assertFalse($this->config->has('application.not_exist'));
        $this->assertFalse($this->config->has('not_exist.name'));
        $this->assertFalse($this->config->has('application.name.not_exist'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::has()
     */
    public function testHasIsIdempotent()
    {
        $this->assertTrue($this->config->has('application.name'));
        $this->assertTrue($this->config->has('application.name'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::all()
     */
    public function testAll()
    {
        $all = [
            'host' => 'localhost',
            'port'    => 80,
            'servers' => [
                'host1',
                'host2',
                'host3'
            ],
            'application' => [
                'name'   => 'configuration',
                'secret' => 's3cr3t',
                'runtime' => null,
            ],
            'user' => null,
        ];
        $this->assertSame($all, $this->config->all());
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::merge()
     */
    public function testMerge()
    {
        $remote = new SimpleConfig(
            [
                'host' => '127.0.0.1'
            ]
        );

        $this->config->get('host');
        $this->config->merge($remote);

        $this->assertSame('127.0.0.1', $this->config['host']);
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::offsetGet()
     */
    public function testOffsetGet()
    {
        $this->assertSame('localhost', $this->config['host']);
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::offsetGet()
     */
    public function testOffsetGetNestedKey()
    {
        $this->assertSame('configuration', $this->config['application.name']);
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::offsetExists()
     */
    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->config['host']));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::offsetExists()
     */
    public function testOffsetExistsReturnsFalseOnNonexistentKey()
    {
        $this->assertFalse(isset($this->config['database']));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::offsetSet()
     */
    public function testOffsetSet()
    {
        $this->config['newkey'] = 'newvalue';
        $this->assertSame('newvalue', $this->config['newkey']);
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::offsetUnset()
     */
    public function testOffsetUnset()
    {
        unset($this->config['application']);
        $this->assertNull($this->config['application']);
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::getIterator()
     */
    public function testGetIteratorReturnsIndependentIterators()
    {
        $first = $this->config->getIterator();
        $second = $this->config->getIterator();

        $this->assertSame('host', $first->key());
        $this->assertSame('host', $second->key());

        $first->next();

        $this->assertSame('port', $first->key());
        $this->assertSame('host', $second->key());
    }

    /**
     * Tests that IteratorAggregate works through foreach.
     *
     * @covers \Arris\Core\Config\AbstractConfig::getIterator()
     */
    public function testIterator()
    {
        /* Create numerically indexed copies of the test config */
        $expectedKeys = ['host', 'port', 'servers', 'application', 'user'];
        $expectedValues = [
            'localhost',
            80,
            ['host1', 'host2', 'host3'],
            [
                'name'   => 'configuration',
                'secret' => 's3cr3t',
                'runtime' => null,
            ],
            null
        ];

        $idxConfig = 0;

        foreach ($this->config as $configKey => $configValue) {
            $this->assertSame($expectedKeys[$idxConfig], $configKey);
            $this->assertSame($expectedValues[$idxConfig], $configValue);
            $idxConfig++;
        }
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testGetShouldNotSet()
    {
        $this->config->get('invalid', 'default');
        $actual = $this->config->get('invalid', 'expected');
        $this->assertSame('expected', $actual);
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::remove()
     */
    public function testRemove()
    {
        $this->config->remove('application');
        $this->assertNull($this->config['application']);
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetScalarThenChildThrows()
    {
        $this->expectException(\Arris\Core\Config\Exception\StructureConflictException::class);

        $config = new SimpleConfig([]);
        $config->set('a', 'bar');
        $config->set('a.b', 'foo');
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetArrayThenScalarThrows()
    {
        $this->expectException(\Arris\Core\Config\Exception\StructureConflictException::class);

        $config = new SimpleConfig([]);
        $config->set('a.b', 'foo');
        $config->set('a', 'bar');
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetAssocDeepMerges()
    {
        $config = new SimpleConfig([]);
        $config->set('a', ['b' => 1]);
        $config->set('a', ['c' => 2]);

        $this->assertSame(['b' => 1, 'c' => 2], $config->get('a'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetListReplaces()
    {
        $config = new SimpleConfig([]);
        $config->set('a', ['h1', 'h2', 'h3']);
        $config->set('a', ['h4']);

        $this->assertSame(['h4'], $config->get('a'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetKindMismatchThrows()
    {
        $this->expectException(\Arris\Core\Config\Exception\StructureConflictException::class);

        $config = new SimpleConfig([]);
        $config->set('a', ['b' => 1]);
        $config->set('a', ['h1']);
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetEmptyArrayClearsArray()
    {
        $config = new SimpleConfig([]);
        $config->set('a', ['b' => 1]);
        $config->set('a', []);

        $this->assertSame([], $config->get('a'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetNullIsStored()
    {
        $config = new SimpleConfig(['a' => ['b' => 1]]);
        $config->set('a.b', null);

        $this->assertTrue($config->has('a.b'));
        $this->assertNull($config->get('a.b'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetNullInsideArrayIsStored()
    {
        $config = new SimpleConfig([]);
        $config->set('a', ['b' => null]);

        $this->assertTrue($config->has('a.b'));
        $this->assertNull($config->get('a.b'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetListWithNullIsStored()
    {
        $config = new SimpleConfig([]);
        $config->set('a', [1, null, 3]);

        $this->assertSame([1, null, 3], $config->get('a'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetNullOverArrayThrows()
    {
        $this->expectException(\Arris\Core\Config\Exception\StructureConflictException::class);

        $config = new SimpleConfig(['a' => ['b' => 1]]);
        $config->set('a', null);
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testEscapedDotAddressesLiteralKey()
    {
        $config = new SimpleConfig([]);
        $config->set('a\\.b', 1);

        $this->assertSame(1, $config->get('a\\.b'));
        $this->assertNull($config->get('a.b'));
        $this->assertArrayHasKey('a.b', $config->all());
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::replace()
     */
    public function testReplaceNodeChangesKind()
    {
        $config = new SimpleConfig([]);
        $config->set('a.b', 'foo');
        $config->replace('a', 'bar');

        $this->assertSame('bar', $config->get('a'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::replace()
     */
    public function testReplaceNodeWithArrayReplacesNotMerges()
    {
        $config = new SimpleConfig([]);
        $config->set('a', ['b' => 1]);
        $config->replace('a', ['c' => 2]);

        $this->assertSame(['c' => 2], $config->get('a'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::replace()
     */
    public function testReplacePathAcceptsNull()
    {
        $config = new SimpleConfig([]);
        $config->set('a', ['b' => 1]);
        $config->replace('a', null);

        $this->assertTrue($config->has('a'));
        $this->assertNull($config->get('a'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::replace()
     */
    public function testReplaceWholeConfig()
    {
        $config = new SimpleConfig(['a' => 1]);
        $config->replace(['b' => 2]);

        $this->assertNull($config->get('a'));
        $this->assertSame(2, $config->get('b'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::replace()
     */
    public function testReplacePathRequiresValue()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new SimpleConfig([]))->replace('a');
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::merge()
     */
    public function testLayerMergeReplacesLists()
    {
        $config = new SimpleConfig(['servers' => ['h1', 'h2', 'h3']]);
        $config->merge(new SimpleConfig(['servers' => ['h4']]));

        $this->assertSame(['h4'], $config->get('servers'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::merge()
     */
    public function testLayerMergeNullDeletes()
    {
        $config = new SimpleConfig(['a' => 1]);
        $config->merge(new SimpleConfig(['a' => null]));

        $this->assertFalse($config->has('a'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::__construct()
     */
    public function testDefaultsDeepMerge()
    {
        $config = new SimpleConfig(['application' => ['name' => 'custom']]);

        $this->assertSame('custom', $config->get('application.name'));
        $this->assertSame('s3cr3t', $config->get('application.secret'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::remove()
     * @covers \Arris\Core\Config\AbstractConfig::has()
     */
    public function testRemoveFromNestedArray()
    {
        $config = new SimpleConfig(['a' => ['b' => 1]]);
        $this->assertSame(1, $config->get('a.b'));

        $config->remove('a');

        $this->assertFalse($config->has('a.b'));
        $this->assertNull($config->get('a.b'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::offsetUnset()
     */
    public function testRemoveDoesNotAffectPrefixKeys()
    {
        $config = new SimpleConfig(['foo' => 1, 'foobar' => 2]);
        $this->assertSame(2, $config->get('foobar'));

        $config->remove('foo');

        $this->assertSame(2, $config->get('foobar'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::offsetSet()
     */
    public function testArrayAccessAppendThrows()
    {
        $this->expectException(\InvalidArgumentException::class);

        $config = new SimpleConfig([]);
        $config[] = 5;
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetListElementReplaces()
    {
        $config = new SimpleConfig([]);
        $config->set('servers', ['h1', 'h2']);
        $config->set('servers.0', 'h0');

        $this->assertSame(['h0', 'h2'], $config->get('servers'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::__construct()
     */
    public function testConstructorKeepsDottedKeysLiteral()
    {
        $config = new SimpleConfig(['a.b' => 1, 'a.c' => 2]);

        $this->assertSame(1, $config->get('a\\.b'));
        $this->assertSame(2, $config->get('a\\.c'));
        $this->assertNull($config->get('a.b'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetScalarToEmptyArrayThrows()
    {
        $this->expectException(\Arris\Core\Config\Exception\StructureConflictException::class);

        $config = new SimpleConfig([]);
        $config->set('a', 'x');
        $config->set('a', []);
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     */
    public function testSetListNonNumericSegmentThrows()
    {
        $this->expectException(\Arris\Core\Config\Exception\StructureConflictException::class);

        $config = new SimpleConfig([]);
        $config->set('servers', ['h1']);
        $config->set('servers.host', 'localhost');
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::replace()
     */
    public function testReplaceIntermediateScalarThrows()
    {
        $this->expectException(\Arris\Core\Config\Exception\StructureConflictException::class);

        $config = new SimpleConfig([]);
        $config->set('a', 'x');
        $config->replace('a.b', 'y');
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::remove()
     */
    public function testRemoveOnlyChildLeavesEmptyArray()
    {
        $config = new SimpleConfig(['a' => ['b' => 1]]);
        $config->remove('a.b');

        $this->assertTrue($config->has('a'));
        $this->assertSame([], $config->get('a'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::set()
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testLiteralAndNestedKeysCoexist()
    {
        $config = new SimpleConfig([]);
        $config->set('a.b', 1);
        $config->set('a\\.b', 2);

        $this->assertSame(1, $config->get('a.b'));
        $this->assertSame(2, $config->get('a\\.b'));

        $config->set('x', $config->get('a'));
        $config->set('y', ['b.c' => 3]);

        $this->assertSame(['b' => 1], $config->get('x'));
        $this->assertSame(3, $config->get('y.b\\.c'));
        $this->assertNull($config->get('y.b.c'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::getAll()
     */
    public function testGetAllReturnsWholeConfig()
    {
        $config = new SimpleConfig(['a' => 1]);

        $this->assertSame($config->all(), $config->getAll());
        $this->assertSame($config->all(), $config->get());
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::get()
     */
    public function testNoArgumentGetReturnsWholeConfigAndAsteriskIsLiteral()
    {
        $config = new SimpleConfig(['*' => 'literal', 'a' => 1]);

        $this->assertSame($config->all(), $config->get());
        $this->assertSame('literal', $config->get('*'));
        $this->assertTrue($config->has('*'));
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::getAllMatch()
     */
    public function testGetAllMatchIsNotImplemented()
    {
        $this->expectException(\Arris\Core\Config\Exception\NotImplementedException::class);

        (new SimpleConfig([]))->getAllMatch('*');
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::get()
     * @covers \Arris\Core\Config\AbstractConfig::has()
     * @covers \Arris\Core\Config\AbstractConfig::set()
     * @covers \Arris\Core\Config\AbstractConfig::remove()
     */
    public function testNonStringKeysAreRejected()
    {
        $config = new SimpleConfig([]);

        foreach ([
            fn() => $config->get([]),
            fn() => $config->has([]),
            fn() => $config->set([], 1),
            fn() => $config->remove([]),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Expected InvalidArgumentException');
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * @covers \Arris\Core\Config\AbstractConfig::replace()
     */
    public function testReplaceAfterReadIsVisible()
    {
        $config = new SimpleConfig(['a' => 1]);
        $this->assertSame(1, $config->get('a'));

        $config->replace(['a' => 2]);

        $this->assertSame(2, $config->get('a'));
    }
}
