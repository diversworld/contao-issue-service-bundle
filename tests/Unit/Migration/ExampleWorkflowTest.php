<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Migration;

use Diversworld\ContaoIssueServiceBundle\Migration\Version122ExampleWorkflow;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

final class ExampleWorkflowTest extends TestCase
{
    public function testSeedingPreservesChangesAndSupportsRepeatedRuns(): void
    {
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $migration = new Version122ExampleWorkflow($db);
        self::assertFalse($migration->shouldRun());
        $db->executeStatement('CREATE TABLE tl_issue_status (id INTEGER PRIMARY KEY, status_key VARCHAR(32))');
        $db->executeStatement('CREATE TABLE tl_issue_transition (id INTEGER PRIMARY KEY, tstamp INTEGER, from_status_id INTEGER, to_status_id INTEGER, role_key VARCHAR(40), require_public_comment INTEGER, published INTEGER, UNIQUE(from_status_id,to_status_id,role_key))');
        self::assertFalse($migration->shouldRun());
        foreach (['new', 'triage', 'in_progress', 'waiting_user', 'resolved', 'closed', 'rejected'] as $i => $key) {
            $db->insert('tl_issue_status', ['id' => 101 + $i, 'status_key' => $key]);
        }
        $db->insert('tl_issue_transition', ['from_status_id' => 101, 'to_status_id' => 102, 'role_key' => 'agent', 'published' => 0, 'require_public_comment' => 1]);
        self::assertTrue($migration->shouldRun());
        $migration->run();
        self::assertSame(23, (int) $db->fetchOne('SELECT COUNT(*) FROM tl_issue_transition'));
        self::assertSame(22, (int) $db->fetchOne('SELECT COUNT(*) FROM tl_issue_transition WHERE published=1'));
        self::assertSame(1, (int) $db->fetchOne('SELECT require_public_comment FROM tl_issue_transition WHERE published=0'));
        self::assertFalse($migration->shouldRun());
        $migration->run();
        self::assertSame(23, (int) $db->fetchOne('SELECT COUNT(*) FROM tl_issue_transition'));
        $db->executeStatement("DELETE FROM tl_issue_transition WHERE role_key='member'");
        self::assertTrue($migration->shouldRun());
        $migration->run();
        self::assertSame(23, (int) $db->fetchOne('SELECT COUNT(*) FROM tl_issue_transition'));
    }
}
