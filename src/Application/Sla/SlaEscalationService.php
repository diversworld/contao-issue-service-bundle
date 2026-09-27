<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\Sla;

use Doctrine\DBAL\Connection;
use Diversworld\ContaoIssueServiceBundle\Application\License\PremiumFeatureResolver;
use Diversworld\ContaoIssueServiceBundle\Application\NotificationService;

final class SlaEscalationService
{
    public function __construct(private readonly Connection $db, private readonly PremiumFeatureResolver $premium,
        private readonly SlaCalculationService $calculator, private readonly SlaHistory $history, private readonly NotificationService $notifications) {}

    public function escalate(?int $now = null): int
    {
        $this->premium->requireSla();
        $now ??= time();
        $this->calculator->calculateAll();
        $count = 0;
        $last = 0;
        do {
            $ids = $this->db->fetchFirstColumn("SELECT id FROM tl_issue WHERE id>? AND deleted_at IS NULL AND sla_state='active' AND sla_breached=1 ORDER BY id LIMIT 200", [$last]);
            foreach ($ids as $id) {
                $last = (int) $id;
                $count += $this->db->transactional(function () use ($id, $now): int {
                    $this->premium->requireSla();
                    $issue = $this->db->fetchAssociative('SELECT * FROM tl_issue WHERE id=? FOR UPDATE', [$id]);
                    if (!$issue || $issue['sla_state'] !== 'active' || $issue['deleted_at']) return 0;
                    $sent = 0;
                    $rules = $this->db->fetchAllAssociative('SELECT * FROM tl_issue_sla_escalation WHERE sla_id=? AND published=1 ORDER BY stage', [$issue['sla_id']]);
                    foreach (['response', 'resolve'] as $kind) {
                        if ($kind === 'response' && $issue['first_response_at']) continue;
                        foreach ($rules as $rule) {
                            if ($now <= (int) $issue[$kind.'_due_at'] + (int) $rule['delay_minutes'] * 60) continue;
                            $event = 'escalated_'.$kind.'_'.$issue['sla_cycle'].'_'.$rule['stage'];
                            if ($this->db->fetchOne('SELECT id FROM tl_issue_sla_history WHERE issue_id=? AND event_type=?', [$id, $event])) continue;
                            $recipients = self::recipients((string) $rule['recipients']);
                            if (!$recipients) continue;
                            $template = 'sla_escalation_'.$rule['id'];
                            $this->notifications->enqueueRecipients((int) $id, $template, $recipients);
                            $this->history->append((int) $id, $event, ['stage' => (int) $rule['stage'], 'kind' => $kind, 'due_at' => (int) $issue[$kind.'_due_at']], $now);
                            ++$sent;
                        }
                    }
                    return $sent;
                });
            }
        } while (count($ids) === 200);
        return $count;
    }

    /** @return list<string> */
    public static function recipients(string $value): array
    {
        $items = preg_split('/[\r\n,;]+/', $value) ?: [];
        return array_values(array_unique(array_filter(array_map(static fn ($email) => strtolower(trim($email)), $items), static fn ($email) => (bool) filter_var($email, FILTER_VALIDATE_EMAIL))));
    }
}
