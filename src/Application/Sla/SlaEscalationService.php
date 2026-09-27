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
        $this->calculator->calculateAll($now);
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
                            $snapshot = json_decode($issue['sla_snapshot'], true, 32, JSON_THROW_ON_ERROR);
                            $trigger = (new BusinessCalendar())->add((int) $issue[$kind.'_due_at'], (int) $rule['delay_minutes'] * 60, $snapshot['timezone'], $snapshot['hours'], $snapshot['holidays'], $snapshot['maintenance'] ?? []);
                            if ($now <= $trigger) continue;
                            $event = 'escalated_'.$kind.'_'.$issue['sla_cycle'].'_'.$rule['stage'];
                            if ($this->db->fetchOne('SELECT id FROM tl_issue_sla_history WHERE issue_id=? AND event_type=?', [$id, $event])) continue;
                            $recipients = self::recipients((string) $rule['recipients']);
                            if (!$recipients && empty($rule['webhook_url']) && empty($rule['target_status_id'])) continue;
                            $template = 'sla_escalation_'.$rule['id'];
                            $this->notifications->enqueueRecipients((int) $id, $template, $recipients);
                            if (!empty($rule['webhook_url'])) {
                                $this->db->insert('tl_issue_sla_webhook', ['issue_id' => $id, 'rule_id' => $rule['id'], 'event_key' => $event,
                                    'payload' => json_encode(['event' => $event, 'ticket_id' => (int) $id, 'ticket_number' => $issue['ticket_number'], 'stage' => (int) $rule['stage'], 'kind' => $kind, 'due_at' => (int) $issue[$kind.'_due_at']], JSON_THROW_ON_ERROR), 'status' => 'pending']);
                            }
                            if (!empty($rule['target_status_id']) && (int) $issue['status_id'] !== (int) $rule['target_status_id'] && !$this->higherStageReached((int) $id, (int) $issue['sla_cycle'], (int) $rule['stage'])) {
                                // Explicit administrator-configured automation; only published, non-terminal targets.
                                $status = $this->db->fetchAssociative('SELECT * FROM tl_issue_status WHERE id=? AND published=1 AND is_resolved=0 AND is_closed=0', [$rule['target_status_id']]);
                                if (!$status) throw new \DomainException('Eskalationsstatus muss aktiv und offen sein.');
                                $this->db->update('tl_issue', ['status_id' => $status['id'], 'updated_at' => date('Y-m-d H:i:s', $now), 'tstamp' => $now, 'version' => (int) $issue['version'] + 1], ['id' => $id]);
                                $this->db->insert('tl_issue_history', ['issue_id' => $id, 'event_type' => 'status_changed', 'actor_type' => 'system', 'actor_id' => 0,
                                    'old_value' => json_encode(['status_id' => (int) $issue['status_id']], JSON_THROW_ON_ERROR), 'new_value' => json_encode(['status_id' => (int) $status['id'], 'source' => $event], JSON_THROW_ON_ERROR), 'created_at' => date('Y-m-d H:i:s', $now)]);
                                $issue['status_id'] = $status['id'];
                                ++$issue['version'];
                            }
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

    private function higherStageReached(int $issueId, int $cycle, int $stage): bool
    {
        for ($higher = $stage + 1; $higher <= 3; ++$higher) {
            foreach (['response', 'resolve'] as $kind) {
                if ($this->db->fetchOne('SELECT id FROM tl_issue_sla_history WHERE issue_id=? AND event_type=?', [$issueId, 'escalated_'.$kind.'_'.$cycle.'_'.$higher])) return true;
            }
        }
        return false;
    }

    /** @return list<string> */
    public static function recipients(string $value): array
    {
        $items = preg_split('/[\r\n,;]+/', $value) ?: [];
        return array_values(array_unique(array_filter(array_map(static fn ($email) => strtolower(trim($email)), $items), static fn ($email) => (bool) filter_var($email, FILTER_VALIDATE_EMAIL))));
    }
}
