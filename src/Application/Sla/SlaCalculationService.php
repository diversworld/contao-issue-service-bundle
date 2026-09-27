<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\Sla;

use Doctrine\DBAL\Connection;
use Diversworld\ContaoIssueServiceBundle\Application\License\PremiumFeatureResolver;

final class SlaCalculationService
{
    private readonly SlaPolicyResolver $policies;

    public function __construct(private readonly Connection $db, private readonly BusinessCalendar $calendar,
        private readonly PremiumFeatureResolver $premium, private readonly SlaHistory $history, ?SlaPolicyResolver $policies = null)
    {
        $this->policies = $policies ?? new SlaPolicyResolver($db, $calendar);
    }

    public function calculateAll(?int $now = null): int
    {
        $this->premium->requireSla();
        $count = 0;
        $last = 0;
        do {
            $ids = $this->db->fetchFirstColumn('SELECT id FROM tl_issue WHERE deleted_at IS NULL AND id>? ORDER BY id LIMIT 200', [$last]);
            foreach ($ids as $id) { $this->synchronize((int) $id, $now); $last = (int) $id; ++$count; }
        } while (count($ids) === 200);
        return $count;
    }

    /** Core workflow remains available when premium expires. */
    public function synchronize(int $issueId, ?int $now = null, int $actorId = 0): void
    {
        if (!$this->premium->enabled()) return;
        $now ??= time();
        $this->db->transactional(function () use ($issueId, $now, $actorId): void {
            $issue = $this->db->fetchAssociative('SELECT * FROM tl_issue WHERE id=? FOR UPDATE', [$issueId]);
            if (!$issue || !empty($issue['deleted_at'])) return;
            $status = $this->db->fetchAssociative('SELECT is_resolved,is_closed FROM tl_issue_status WHERE id=?', [$issue['status_id']]);
            if (!$status) return;
            $terminal = $status['is_resolved'] || $status['is_closed'];
            $start = $issue['created_at'] ? (new \DateTimeImmutable($issue['created_at']))->getTimestamp() : $now;
            $policy = $this->policies->resolve($issue, $start);
            $slaId = (int) ($policy['sla_id'] ?? 0);
            $patch = [];
            $event = 'calculated';
            if (!$slaId) {
                if ((int) $issue['sla_id']) $this->save($issue, ['sla_id' => 0, 'sla_state' => 'none', 'response_due_at' => null, 'resolve_due_at' => null, 'sla_snapshot' => null], 'unassigned', $now, $actorId);
                return;
            }
            $previous = json_decode($issue['sla_snapshot'] ?: 'null', true, 32, JSON_THROW_ON_ERROR);
            $assigned = (int) $issue['sla_id'] !== $slaId || !$previous || (isset($previous['identity']) && $previous !== $policy);
            if (!$assigned && $policy && !isset($previous['identity'])) {
                // Upgrade legacy snapshots without resetting paused budgets, deadlines or escalation cycles.
                $patch['sla_snapshot'] = json_encode($policy, JSON_THROW_ON_ERROR);
            }
            if ($assigned) {
                $snapshot = $policy ?? throw new \LogicException('SLA policy unavailable.');
                // Reassignment never restarts the clock at the edit time.
                $patch = ['sla_id' => $slaId, 'sla_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'sla_state' => 'active',
                    'response_due_at' => $this->add($start, $snapshot['response'], $snapshot), 'resolve_due_at' => $this->add($start, $snapshot['resolve'], $snapshot),
                    'sla_paused_at' => null, 'sla_cycle' => (int) $issue['sla_cycle'] + 1, 'sla_response_breached' => 0, 'sla_resolve_breached' => 0];
                $event = 'assigned';
            } else {
                $snapshot = json_decode($issue['sla_snapshot'], true, 32, JSON_THROW_ON_ERROR);
            }
            $current = array_replace($issue, $patch);
            $response = $this->db->fetchOne("SELECT MIN(created_at) FROM tl_issue_comment WHERE issue_id=? AND visibility='public' AND author_type='user'", [$issueId]);
            if (!$current['first_response_at'] && $response) {
                $patch['first_response_at'] = (new \DateTimeImmutable((string) $response))->getTimestamp();
                $current['first_response_at'] = $patch['first_response_at'];
                if (!$assigned) $event = 'responded';
            }
            if (!$terminal && $current['sla_state'] === 'paused') {
                $patch['resolve_due_at'] = $this->add($now, max(0, (int) $current['sla_remaining_seconds']), $snapshot);
                if ((int) $current['sla_remaining_seconds'] < 0) $patch['resolve_due_at'] = $now - 1;
                if (!$current['first_response_at']) {
                    $remaining = $this->between((int) $current['sla_paused_at'], (int) $current['response_due_at'], $snapshot);
                    $patch['response_due_at'] = (int) $current['sla_paused_at'] > (int) $current['response_due_at'] ? $now - 1 : $this->add($now, max(0, $remaining), $snapshot);
                }
                $patch['sla_state'] = 'active';
                $patch['sla_paused_at'] = null;
                $event = 'reopened';
                $current = array_replace($current, $patch);
            }
            $stop = $now;
            if ($terminal) {
                $dates = array_filter([$issue['resolved_at'], $issue['closed_at']]);
                if ($dates) $stop = min(array_map(static fn ($date) => (new \DateTimeImmutable($date))->getTimestamp(), $dates));
                if ($current['sla_state'] !== 'paused') {
                    $patch['sla_state'] = 'paused';
                    $patch['sla_paused_at'] = $stop;
                    $remaining = $this->between($stop, (int) $current['resolve_due_at'], $snapshot);
                    $patch['sla_remaining_seconds'] = $stop > (int) $current['resolve_due_at'] ? min(-1, $remaining) : $remaining;
                    $event = 'paused';
                }
            }
            foreach (['response' => $current['first_response_at'] ? (int) $current['first_response_at'] : $stop, 'resolve' => $stop] as $kind => $at) {
                if ($at > (int) $current[$kind.'_due_at'] && !$current['sla_'.$kind.'_breached']) {
                    $patch['sla_'.$kind.'_breached'] = 1;
                    $patch['sla_breached'] = 1;
                    $patch['sla_breached_at'] = min((int) ($patch['sla_breached_at'] ?? $current['sla_breached_at'] ?: $current[$kind.'_due_at']), (int) $current[$kind.'_due_at']);
                    $this->history->append($issueId, $kind.'_breached', ['due_at' => (int) $current[$kind.'_due_at'], 'cycle' => (int) $current['sla_cycle']], $now, $actorId);
                }
            }
            if ($patch) $this->save($issue, $patch, $event, $now, $actorId);
        });
    }

    /** @return array<string, mixed>|null */
    public function view(int $issueId, ?int $now = null): ?array
    {
        if (!$this->premium->enabled()) return null;
        $issue = $this->db->fetchAssociative('SELECT * FROM tl_issue WHERE id=? AND deleted_at IS NULL', [$issueId]);
        if (!$issue || !$issue['sla_snapshot'] || !$issue['sla_id']) return null;
        $snapshot = json_decode($issue['sla_snapshot'], true, 32, JSON_THROW_ON_ERROR);
        $at = $issue['sla_state'] === 'paused' ? (int) $issue['sla_paused_at'] : ($now ?? time());
        return ['state' => $issue['sla_state'], 'breached' => (bool) $issue['sla_breached'], 'response_due_at' => $issue['response_due_at'], 'resolve_due_at' => $issue['resolve_due_at'],
            'responded' => (bool) $issue['first_response_at'],
            'response_overdue' => !$issue['first_response_at'] && $at > (int) $issue['response_due_at'],
            'resolve_overdue' => $at > (int) $issue['resolve_due_at'],
            'response_remaining' => $issue['first_response_at'] ? 0 : $this->between($at, (int) $issue['response_due_at'], $snapshot),
            'resolve_remaining' => $this->between($at, (int) $issue['resolve_due_at'], $snapshot),
            // Internal configuration, actor identities and recipients are deliberately excluded.
            'history' => $this->db->fetchAllAssociative('SELECT event_type,created_at FROM tl_issue_sla_history WHERE issue_id=? ORDER BY id', [$issueId])];
    }

    /** @param array<string, mixed> $snapshot */
    private function add(int $start, int $seconds, array $snapshot): int
    {
        return $this->calendar->add($start, $seconds, $snapshot['timezone'], $snapshot['hours'], $snapshot['holidays'], $snapshot['maintenance'] ?? []);
    }
    /** @param array<string, mixed> $snapshot */
    private function between(int $start, int $end, array $snapshot): int
    {
        return $this->calendar->between($start, $end, $snapshot['timezone'], $snapshot['hours'], $snapshot['holidays'], $snapshot['maintenance'] ?? []);
    }
    /** @param array<string, mixed> $issue
     * @param array<string, mixed> $patch */
    private function save(array $issue, array $patch, string $event, int $now, int $actorId): void
    {
        $this->db->update('tl_issue', $patch, ['id' => $issue['id']]);
        $this->history->append((int) $issue['id'], $event, ['before' => array_intersect_key($issue, $patch), 'after' => $patch], $now, $actorId);
    }
}
