<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Repository;

use Diversworld\ContaoIssueServiceBundle\Repository\IssueRepository;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

final class IssueTimelineTest extends TestCase
{
    public function testPublicTimelineExcludesInternalNotesAndEvents(): void
    {
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('CREATE TABLE tl_issue_comment (id INTEGER PRIMARY KEY, issue_id INTEGER, body TEXT, created_at TEXT, author_type TEXT, visibility TEXT)');
        $db->executeStatement('CREATE TABLE tl_issue_history (id INTEGER PRIMARY KEY, issue_id INTEGER, event_type TEXT, old_value TEXT, new_value TEXT, created_at TEXT, actor_type TEXT)');
        $db->executeStatement('CREATE TABLE tl_issue_status (id INTEGER PRIMARY KEY, title TEXT)');
        $db->insert('tl_issue_status', ['id' => 1, 'title' => 'Neu']);
        $db->insert('tl_issue_status', ['id' => 2, 'title' => 'In Bearbeitung']);
        foreach ([['public', 'Kundenantwort'], ['internal', 'Geheime Notiz']] as [$visibility, $body]) {
            $db->insert('tl_issue_comment', ['issue_id' => 1, 'body' => $body, 'visibility' => $visibility, 'author_type' => 'user', 'created_at' => '2026-09-25 10:00:00']);
        }
        foreach (['public_comment_added', 'internal_comment_added', 'status_changed', 'assignment_changed', 'resolution_changed'] as $event) {
            $db->insert('tl_issue_history', ['issue_id' => 1, 'event_type' => $event, 'actor_type' => 'user', 'created_at' => '2026-09-25 11:00:00', 'old_value' => '{"status_id":1}', 'new_value' => '{"status_id":2}']);
        }
        $db->insert('tl_issue_comment', ['issue_id' => 2, 'body' => 'Anderes Ticket', 'visibility' => 'public', 'created_at' => '2026-09-25 10:00:00']);
        $repo = new IssueRepository($db);
        $public = $repo->publicTimeline(1);
        self::assertSame(['Kundenantwort', 'Status: Neu → In Bearbeitung'], array_column($public, 'text'));
        $journal = $repo->timeline(1, true);
        self::assertCount(5, $journal);
        self::assertContains('Geheime Notiz', array_column($journal, 'text'));
        self::assertNotContains('Anderes Ticket', array_column($journal, 'text'));
        self::assertSame(['internal', 'internal', 'internal'], array_values(array_column(array_filter($journal, static fn (array $entry): bool => $entry['visibility'] === 'internal'), 'visibility')));
        $db->close();
    }
}
