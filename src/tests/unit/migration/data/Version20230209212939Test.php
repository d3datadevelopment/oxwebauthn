<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * https://www.d3data.de
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author    D3 Data Development - Daniel Seifert <info@shopmodule.com>
 * @link      https://www.oxidmodule.com
 */

declare(strict_types=1);

namespace D3\Webauthn\tests\unit\migration\data;

use D3\TestingTools\Development\CanAccessRestricted;
use D3\Webauthn\Migrations\Version20230209212939;
use D3\Webauthn\tests\unit\WAUnitTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Version\Version;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;

class Version20230209212939Test extends WAUnitTestCase
{
    use CanAccessRestricted;

    /** @var Version20230209212939 */
    protected $sut;

    public function setUp(): void
    {
        parent::setUp();

        /** @var AbstractPlatform|MockObject $databasePlatformMock */
        $databasePlatformMock = $this->getMockBuilder(MySQL57Platform::class)
            ->getMock();

        /** @var AbstractSchemaManager|MockObject $schemaManagerMock */
        $schemaManagerMock = $this->getMockBuilder(MySqlSchemaManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Connection|MockObject $connectionMock */
        $connectionMock = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDatabasePlatform', 'getSchemaManager'])
            ->getMock();
        $connectionMock->method('getDatabasePlatform')->willReturn($databasePlatformMock);
        $connectionMock->method('getSchemaManager')->willReturn($schemaManagerMock);

        /** @var Configuration|MockObject $configurationMock */
        $configurationMock = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection'])
            ->getMock();
        $configurationMock->method('getConnection')->willReturn($connectionMock);

        /** @var Version|MockObject $versionMock */
        $versionMock = $this->getMockBuilder(Version::class)
            ->onlyMethods(['getConfiguration'])
            ->disableOriginalConstructor()
            ->getMock();
        $versionMock->method('getConfiguration')->willReturn($configurationMock);

        $this->sut = oxNew(Version20230209212939::class, $versionMock);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Migrations\Version20230209212939::getDescription
     */
    public function canGetDescription()
    {
        $this->assertIsString(
            $this->callMethod(
                $this->sut,
                'getDescription'
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Migrations\Version20230209212939::up
     * @dataProvider canUpTableDataProvider
     */
    public function canUpTable($tableExist, $invocationCount)
    {
        /** @var Table|MockObject $tableMock */
        $tableMock = $this->getMockBuilder(Table::class)
            ->onlyMethods(['hasColumn', 'hasPrimaryKey', 'hasIndex'])
            ->disableOriginalConstructor()
            ->getMock();
        $tableMock->method('hasColumn')->willReturn(true);
        $tableMock->method('hasPrimaryKey')->willReturn(true);
        $tableMock->method('hasIndex')->willReturn(true);

        /** @var Schema|MockObject $schemaMock */
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['hasTable', 'createTable', 'getTable'])
            ->getMock();
        $schemaMock->method('hasTable')->willReturn($tableExist);
        $schemaMock->expects($invocationCount)->method('createTable')->willReturn($tableMock);
        $schemaMock->method('getTable')->willReturn($tableMock);

        $this->callMethod(
            $this->sut,
            'up',
            [$schemaMock]
        );
    }

    /**
     * @return Generator
     */
    public function canUpTableDataProvider(): Generator
    {
        yield 'table not exist' => [false, $this->once()];
        yield 'table exist'     => [true, $this->never()];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Migrations\Version20230209212939::up
     * @dataProvider canUpColumnDataProvider
     */
    public function canUpColumn($columnExist, $invocationCount)
    {
        /** @var Column|MockObject $columnMock */
        $columnMock = $this->getMockBuilder(Column::class)
            ->onlyMethods(['setLength'])
            ->disableOriginalConstructor()
            ->getMock();
        $columnMock->method('setLength')->willReturnSelf();

        /** @var Table|MockObject $tableMock */
        $tableMock = $this->getMockBuilder(Table::class)
            ->onlyMethods(['hasColumn', 'addColumn', 'hasPrimaryKey', 'hasIndex'])
            ->disableOriginalConstructor()
            ->getMock();
        $tableMock->method('hasColumn')->willReturn($columnExist);
        $tableMock->expects($invocationCount)->method('addColumn')->willReturn($columnMock);
        $tableMock->method('hasPrimaryKey')->willReturn(true);
        $tableMock->method('hasIndex')->willReturn(true);

        /** @var Schema|MockObject $schemaMock */
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['hasTable', 'getTable'])
            ->getMock();
        $schemaMock->method('hasTable')->willReturn(true);
        $schemaMock->method('getTable')->willReturn($tableMock);

        $this->callMethod(
            $this->sut,
            'up',
            [$schemaMock]
        );
    }

    /**
     * @return Generator
     */
    public function canUpColumnDataProvider(): Generator
    {
        yield 'column not exist' => [false, $this->atLeast(7)];
        yield 'column exist'     => [true, $this->never()];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Migrations\Version20230209212939::up
     * @dataProvider canUpPrimaryKeyDataProvider
     */
    public function canUpPrimaryKey($pKeyExist, $invocationCount)
    {
        /** @var Table|MockObject $tableMock */
        $tableMock = $this->getMockBuilder(Table::class)
            ->onlyMethods(['hasColumn', 'addColumn', 'hasPrimaryKey', 'hasIndex', 'setPrimaryKey'])
            ->disableOriginalConstructor()
            ->getMock();
        $tableMock->method('hasColumn')->willReturn(true);
        $tableMock->method('hasPrimaryKey')->willReturn($pKeyExist);
        $tableMock->method('hasIndex')->willReturn(true);
        $tableMock->expects($invocationCount)->method('setPrimaryKey');

        /** @var Schema|MockObject $schemaMock */
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['hasTable', 'getTable'])
            ->getMock();
        $schemaMock->method('hasTable')->willReturn(true);
        $schemaMock->method('getTable')->willReturn($tableMock);

        $this->callMethod(
            $this->sut,
            'up',
            [$schemaMock]
        );
    }

    /**
     * @return Generator
     */
    public function canUpPrimaryKeyDataProvider(): Generator
    {
        yield 'pk not exist' => [false, $this->once()];
        yield 'pk exist'     => [true, $this->never()];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Migrations\Version20230209212939::up
     * @dataProvider canUpIndexDataProvider
     */
    public function canUpIndex($indexExist, $invocationCount)
    {
        /** @var Table|MockObject $tableMock */
        $tableMock = $this->getMockBuilder(Table::class)
            ->onlyMethods(['hasColumn', 'addColumn', 'hasPrimaryKey', 'hasIndex', 'addIndex', 'setComment'])
            ->disableOriginalConstructor()
            ->getMock();
        $tableMock->method('hasColumn')->willReturn(true);
        $tableMock->method('hasPrimaryKey')->willReturn(true);
        $tableMock->method('hasIndex')->willReturn($indexExist);
        $tableMock->expects($invocationCount)->method('addIndex');
        $tableMock->expects($this->once())->method('setComment');

        /** @var Schema|MockObject $schemaMock */
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['hasTable', 'getTable'])
            ->getMock();
        $schemaMock->method('hasTable')->willReturn(true);
        $schemaMock->method('getTable')->willReturn($tableMock);

        $this->callMethod(
            $this->sut,
            'up',
            [$schemaMock]
        );
    }

    /**
     * @return Generator
     */
    public function canUpIndexDataProvider(): Generator
    {
        yield 'index not exist' => [false, $this->atLeast(2)];
        yield 'index exist'     => [true, $this->never()];
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @covers \D3\Webauthn\Migrations\Version20230209212939::down
     * @dataProvider canDownTableDataProvider
     */
    public function canDownTable($tableExist, $invocationCount)
    {
        /** @var Schema|MockObject $schemaMock */
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['hasTable', 'dropTable'])
            ->getMock();
        $schemaMock->method('hasTable')->willReturn($tableExist);
        $schemaMock->expects($invocationCount)->method('dropTable');

        $this->callMethod(
            $this->sut,
            'down',
            [$schemaMock]
        );
    }

    /**
     * @return Generator
     */
    public function canDownTableDataProvider(): Generator
    {
        yield 'table exist' => [true, $this->once()];
        yield 'table not exist'     => [false, $this->never()];
    }
}