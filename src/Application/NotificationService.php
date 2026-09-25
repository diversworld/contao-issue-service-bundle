<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application;

use Doctrine\DBAL\Connection;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;

final class NotificationService
{
    private bool $queued = false;

    public function __construct(
        private readonly Connection $db,
        private readonly SettingsService $settings,
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress,
        private readonly ?LockFactory $lockFactory = null,
    ) {}

    public function hasQueuedNotifications(): bool { return $this->queued; }

    /** Re-evaluated before delivery so opting out also affects queued messages. */
    private function recipients(int $issueId, string $template): array
    {
        if (!in_array($template, ['issue_created', 'assignment_changed', 'status_changed', 'public_comment_added'], true)) return [];
        $field = 'issue_notify_'.$template;
        $issue = $this->db->fetchAssociative('SELECT i.*,s.notification_recipients FROM tl_issue i JOIN tl_issue_service s ON s.id=i.service_id WHERE i.id=:id', ['id' => $issueId]);
        if (!$issue) return [];
        $settings = $this->settings->forProfile((int) ($issue['profile_id'] ?? 0));
        if (!$settings->bool($field, true)) return [];
        $recipients = $settings->json('mail_recipients', []);
        foreach (SettingsList::decode($issue['notification_recipients']) as $entry) {
            array_push($recipients, ...preg_split('/[\r\n,;]+/', $entry));
        }
        if (!empty($issue['assigned_user_id'])) {
            $email = $this->db->fetchOne('SELECT email FROM tl_user WHERE id=?', [(int) $issue['assigned_user_id']]);
            if ($email) $recipients[] = $email;
        }
        $owner = !empty($issue['member_id']) ? $this->db->fetchAssociative('SELECT * FROM tl_member WHERE id=?', [(int) $issue['member_id']]) : false;
        if ($owner && !empty($owner[$field]) && !empty($owner['email'])) $recipients[] = $owner['email'];
        $recipients = array_unique(array_map(static fn ($email): string => strtolower(trim((string) $email)), $recipients));
        return array_values(array_filter($recipients, function (string $email) use ($owner, $field): bool {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
            // Explicit personal opt-outs take precedence even over configured recipients.
            foreach ($this->db->fetchAllAssociative('SELECT * FROM tl_user WHERE LOWER(email)=?', [$email]) as $user) {
                if (array_key_exists($field, $user) && empty($user[$field])) return false;
            }
            if ($owner && strtolower(trim((string) $owner['email'])) === $email && empty($owner[$field])) return false;
            return true;
        }));
    }

    public function enqueue(int $issueId, string $template): void
    {
        $recipients = $this->recipients($issueId, $template);
        $event = Uuid::v7()->toBinary();
        foreach ($recipients as $recipient) {
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) continue;
            $this->db->insert('tl_issue_notification', [
                'event_uuid' => $event, 'issue_id' => $issueId, 'recipient' => $recipient,
                'template_key' => $template, 'status' => 'pending', 'attempt_count' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->queued = true;
        }
    }

    public function dispatchPending(int $limit = 50): int
    {
        // Never send notifications for a transaction that could still roll back.
        if ($this->db->isTransactionActive()) return 0;
        $lock = $this->lockFactory?->createLock('issue-service-notifications', 300);
        if ($lock && !$lock->acquire()) return 0;
        try {
            $rows = $this->db->fetchAllAssociative("SELECT n.*,i.ticket_number,i.title FROM tl_issue_notification n JOIN tl_issue i ON i.id=n.issue_id WHERE n.status IN ('pending','retry') AND (n.next_attempt_at IS NULL OR n.next_attempt_at<=?) ORDER BY n.id LIMIT ".max(1, $limit), [date('Y-m-d H:i:s')]);
            $sent = 0;
            foreach ($rows as $row) {
                $lock?->refresh();
                try {
                    if (!in_array(strtolower(trim($row['recipient'])), $this->recipients((int) $row['issue_id'], $row['template_key']), true)) {
                        $this->db->update('tl_issue_notification', ['status' => 'skipped', 'last_error' => null, 'next_attempt_at' => null], ['id' => $row['id']]);
                        continue;
                    }
                    $activity = match ($row['template_key']) {
                        'issue_created' => 'Ein neues Ticket wurde erstellt.',
                        'assignment_changed' => 'Die Bearbeiterzuweisung des Tickets wurde geändert.',
                        'status_changed' => 'Der Status des Tickets wurde geändert.',
                        'public_comment_added' => 'Eine neue öffentliche Antwort wurde hinzugefügt.',
                        default => 'Es liegt eine neue Aktivität vor.',
                    };
                    $from = $this->fromAddress ?: (string) \Contao\Config::get('adminEmail');
                    $this->mailer->send((new Email())->from($from)->to($row['recipient'])
                        ->subject('['.$row['ticket_number'].'] '.$row['title'])
                        ->text($activity."\n\nTicket: ".$row['ticket_number']."\nBetreff: ".$row['title']));
                    $this->db->update('tl_issue_notification', ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s'), 'last_error' => null, 'next_attempt_at' => null], ['id' => $row['id']]);
                    ++$sent;
                } catch (\Throwable $exception) {
                    $attempt = (int) $row['attempt_count'] + 1;
                    $this->db->update('tl_issue_notification', [
                        'status' => 'retry', 'attempt_count' => $attempt, 'last_error' => substr($exception->getMessage(), 0, 1000),
                        'next_attempt_at' => (new \DateTimeImmutable('+'.min(1440, 2 ** min($attempt, 10)).' minutes'))->format('Y-m-d H:i:s'),
                    ], ['id' => $row['id']]);
                }
            }
            $this->queued = false;
            return $sent;
        } finally {
            $lock?->release();
        }
    }
}
