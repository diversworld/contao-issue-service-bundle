<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Migration;

use Diversworld\ContaoIssueServiceBundle\Migration\Version200Sla;
use Doctrine\DBAL\{Connection, DriverManager};
use PHPUnit\Framework\TestCase;

final class SlaSchemaTest extends TestCase
{
    public function testAdditiveMigrationAndRepeatedRun(): void
    {
        $real = DriverManager::getConnection(['driver'=>'pdo_sqlite','memory'=>true]);
        $real->executeStatement('CREATE TABLE tl_issue (id INTEGER PRIMARY KEY, title TEXT)');
        $real->executeStatement('CREATE TABLE tl_issue_service (id INTEGER PRIMARY KEY, title TEXT)');
        $real->insert('tl_issue', ['id'=>42,'title'=>'Existing ticket']);
        $db = $this->createStub(Connection::class);
        $db->method('createSchemaManager')->willReturn($real->createSchemaManager());
        $db->method('fetchOne')->willReturnCallback(fn ($sql, $params=[]) => $real->fetchOne($sql,$params));
        $db->method('insert')->willReturnCallback(fn ($table,$data) => $real->insert($table,$data));
        $db->method('executeStatement')->willReturnCallback(static function (string $sql) use ($real): int {
            // Exercise the real migration using SQLite; adapt only MySQL-specific DDL syntax.
            if (preg_match('/^ALTER TABLE (\w+) ADD (UNIQUE )?INDEX (\w+) \(([^)]+)\)$/', $sql, $m)) {
                return (int)$real->executeStatement('CREATE '.($m[2] ?: '').'INDEX '.$m[3].' ON '.$m[1].' ('.$m[4].')');
            }
            if (str_starts_with($sql, 'CREATE TABLE')) {
                if (preg_match('/^CREATE TABLE (\w+)/', $sql, $table) !== 1) throw new \RuntimeException('Unexpected table DDL.');
                preg_match_all('/,(UNIQUE )?INDEX (\w+) \(([^)]+)\)/', $sql, $indexes, PREG_SET_ORDER);
                $sql = preg_replace('/,(UNIQUE )?INDEX \w+ \([^)]+\)/', '', $sql) ?? throw new \RuntimeException('Invalid index expression.');
                $sql = preg_replace('/`id` int unsigned NOT NULL auto_increment/i', '`id` INTEGER PRIMARY KEY AUTOINCREMENT', $sql) ?? throw new \RuntimeException('Invalid identity expression.');
                $sql = str_replace(',PRIMARY KEY (id)', '', $sql);
                $sql = preg_replace('/ DEFAULT CHARACTER SET .*$/', '', $sql) ?? throw new \RuntimeException('Invalid table expression.');
                $result = (int)$real->executeStatement($sql);
                foreach ($indexes as $index) $real->executeStatement('CREATE '.($index[1] ?: '').'INDEX '.$index[2].' ON '.$table[1].' ('.$index[3].')');
                return $result;
            }
            return (int)$real->executeStatement($sql);
        });
        $migration = new Version200Sla($db);
        self::assertTrue($migration->shouldRun());
        self::assertTrue($migration->run()->isSuccessful());
        self::assertFalse($migration->shouldRun());
        foreach (['tl_issue_sla_calendar','tl_issue_sla_contract','tl_issue_sla_priority','tl_issue_sla_webhook'] as $table) self::assertTrue($real->createSchemaManager()->tablesExist([$table]));
        self::assertArrayHasKey('calendar_id', $real->createSchemaManager()->listTableColumns('tl_issue_sla'));
        self::assertArrayHasKey('webhook_url', $real->createSchemaManager()->listTableColumns('tl_issue_sla_escalation'));
        self::assertTrue($migration->run()->isSuccessful());
        self::assertSame('Existing ticket', $real->fetchOne('SELECT title FROM tl_issue WHERE id=42'));
        self::assertSame(4, (int)$real->fetchOne('SELECT COUNT(*) FROM tl_issue_sla_level'));
        foreach (['tl_issue' => ['sla_state_response_due_at', 'sla_state_resolve_due_at'], 'tl_issue_sla_escalation' => ['sla_id_stage'], 'tl_issue_sla_history' => ['issue_id_created_at'], 'tl_issue_sla_level' => ['level_key']] as $table => $names) {
            foreach ($names as $name) self::assertArrayHasKey($name, $real->createSchemaManager()->listTableIndexes($table));
        }
        self::assertSame(1, (int)$real->fetchOne('SELECT COUNT(*) FROM tl_issue_license'));
        foreach (['sla_id','sla_state','response_due_at','resolve_due_at','first_response_at','sla_breached','sla_breached_at'] as $column) self::assertArrayHasKey($column, $real->createSchemaManager()->listTableColumns('tl_issue'));
    }
}
