<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;

use Diversworld\ContaoIssueServiceBundle\Application\{NotificationService, SettingsService};
use Diversworld\ContaoIssueServiceBundle\Repository\SettingsRepository;
use Doctrine\DBAL\{Connection, DriverManager};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class NotificationServiceTest extends TestCase
{
    private function database(): Connection
    {
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        foreach ([
            'tl_issue' => 'id INTEGER PRIMARY KEY, service_id INTEGER, profile_id INTEGER, assigned_user_id INTEGER, ticket_number TEXT, title TEXT',
            'tl_issue_service' => 'id INTEGER PRIMARY KEY, notification_recipients TEXT',
            'tl_issue_profile' => 'id INTEGER PRIMARY KEY, mail_recipients TEXT',
            'tl_user' => 'id INTEGER PRIMARY KEY, email TEXT',
            'tl_issue_notification' => 'id INTEGER PRIMARY KEY, event_uuid BLOB, issue_id INTEGER, recipient TEXT, template_key TEXT, status TEXT, attempt_count INTEGER, created_at TEXT, next_attempt_at TEXT, sent_at TEXT, last_error TEXT',
        ] as $table => $schema) $db->executeStatement('CREATE TABLE '.$table.' ('.$schema.')');
        $db->insert('tl_issue', ['id' => 1, 'service_id' => 1, 'profile_id' => 1, 'assigned_user_id' => 3, 'ticket_number' => 'T-1', 'title' => 'Test']);
        $db->insert('tl_issue_service', ['id' => 1, 'notification_recipients' => "service@example.com\nAGENT@example.com\ninvalid"]);
        $db->insert('tl_issue_profile', ['id' => 1, 'mail_recipients' => 'profile@example.com']);
        $db->insert('tl_user', ['id' => 3, 'email' => 'agent@example.com']);
        return $db;
    }

    public function testCreationAndAssignmentReachUniqueConfiguredRecipientsAndAssignee(): void
    {
        $db = $this->database();
        $sent = [];
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::exactly(5))->method('send')->willReturnCallback(static function (Email $email) use (&$sent): void { $sent[] = $email; });
        $service = new NotificationService($db, new SettingsService(new SettingsRepository($db)), $mailer, 'test@example.com');
        $service->enqueue(1, 'issue_created');
        self::assertTrue($service->hasQueuedNotifications());
        self::assertSame(3, $service->dispatchPending());
        self::assertStringContainsString('neues Ticket', $sent[0]->getTextBody());
        $db->update('tl_issue_service', ['notification_recipients' => '[]'], ['id' => 1]);
        $service->enqueue(1, 'assignment_changed');
        self::assertSame(2, $service->dispatchPending());
        self::assertStringContainsString('Bearbeiterzuweisung', $sent[3]->getTextBody());
        self::assertSame(0, $service->dispatchPending());
        self::assertSame(5, (int) $db->fetchOne("SELECT COUNT(*) FROM tl_issue_notification WHERE status='sent'"));
    }

    public function testRollbackNeverSendsAndFailuresAreRetriedLater(): void
    {
        $db = $this->database();
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::exactly(3))->method('send')->willThrowException(new \RuntimeException('Test transport unavailable'));
        $service = new NotificationService($db, new SettingsService(new SettingsRepository($db)), $mailer, 'test@example.com');
        $db->beginTransaction();
        $service->enqueue(1, 'issue_created');
        self::assertSame(0, $service->dispatchPending());
        $db->rollBack();
        self::assertSame(0, $service->dispatchPending());
        $service->enqueue(1, 'issue_created');
        self::assertSame(0, $service->dispatchPending());
        self::assertSame(3, (int) $db->fetchOne("SELECT COUNT(*) FROM tl_issue_notification WHERE status='retry' AND attempt_count=1 AND next_attempt_at IS NOT NULL"));
        self::assertSame(0, $service->dispatchPending());
    }
}
