<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Infrastructure;

use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Diversworld\ContaoIssueServiceBundle\Infrastructure\ContaoSchemaProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\{MySQLPlatform, SQLitePlatform};
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\TestCase;

final class ContaoSchemaProviderTest extends TestCase
{
    public function testMissingEnginesPreserveExistingTablesAndIndexes(): void
    {
        $schema = new Schema();
        foreach (['tl_message_queue', 'tl_news_categories', 'tl_nc_bulky_items'] as $name) {
            $table = $schema->createTable($name);
            $table->addColumn('id', 'integer');
            $table->addColumn('uuid', 'string');
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['uuid'], 'uuid_unique');
        }
        $schema->getTable('tl_news_categories')->addOption('engine', '');
        $inner = $this->createStub(SchemaProvider::class);
        $inner->method('createSchema')->willReturn($schema);
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $connection->expects(self::exactly(3))->method('fetchAssociative')->willReturnCallback(
            static fn (string $sql, array $params): array => ['Engine' => $params[0] === 'tl_message_queue' ? 'MyISAM' : 'InnoDB'],
        );
        $provider = new ContaoSchemaProvider($inner, $connection);
        self::assertSame($schema, $provider->createSchema());
        self::assertSame('MyISAM', $schema->getTable('tl_message_queue')->getOption('engine'));
        self::assertSame('InnoDB', $schema->getTable('tl_news_categories')->getOption('engine'));
        self::assertSame('InnoDB', $schema->getTable('tl_nc_bulky_items')->getOption('engine'));
        foreach ($schema->getTables() as $table) {
            self::assertCount(2, $table->getIndexes());
            self::assertTrue($table->getIndex('uuid_unique')->isUnique());
        }
        // Idempotent: a second pass does not need further metadata queries.
        self::assertSame($schema, $provider->createSchema());
    }

    public function testExplicitOptionsAndNonContaoTablesAreUnchanged(): void
    {
        $schema = new Schema();
        $schema->createTable('tl_custom')->addOption('engine', 'MEMORY');
        $schema->createTable('external_table');
        $inner = $this->createStub(SchemaProvider::class);
        $inner->method('createSchema')->willReturn($schema);
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $connection->expects(self::never())->method('fetchAssociative');
        (new ContaoSchemaProvider($inner, $connection))->createSchema();
        self::assertSame('MEMORY', $schema->getTable('tl_custom')->getOption('engine'));
        self::assertFalse($schema->getTable('external_table')->hasOption('engine'));
    }

    public function testNewTableUsesConfiguredEngineOrInnoDbFallback(): void
    {
        foreach ([[], ['engine'=>''], ['engine'=>'Aria']] as $options) {
            $schema = new Schema();
            $schema->createTable('tl_new');
            $inner = $this->createStub(SchemaProvider::class);
            $inner->method('createSchema')->willReturn($schema);
            $connection = $this->createStub(Connection::class);
            $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
            $connection->method('getParams')->willReturn(['defaultTableOptions'=>$options]);
            $connection->method('fetchAssociative')->willReturn(false);
            (new ContaoSchemaProvider($inner, $connection))->createSchema();
            self::assertSame(($options['engine'] ?? '') ?: 'InnoDB', $schema->getTable('tl_new')->getOption('engine'));
        }
    }

    public function testOtherPlatformsDoNotIssueMySqlQueries(): void
    {
        $schema = new Schema();
        $schema->createTable('tl_new');
        $inner = $this->createStub(SchemaProvider::class);
        $inner->method('createSchema')->willReturn($schema);
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new SQLitePlatform());
        $connection->expects(self::never())->method('fetchAssociative');
        self::assertSame($schema, (new ContaoSchemaProvider($inner, $connection))->createSchema());
        self::assertFalse($schema->getTable('tl_new')->hasOption('engine'));
    }
}
