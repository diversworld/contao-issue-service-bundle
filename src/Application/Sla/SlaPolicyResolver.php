<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\Sla;

use Doctrine\DBAL\Connection;

/** Resolve contracts at ticket creation; the resulting policy is frozen on the ticket. */
final class SlaPolicyResolver
{
    public function __construct(private readonly Connection $db, private readonly BusinessCalendar $calendar) {}

    /** @param array<string, mixed> $issue
     * @return array<string, mixed>|null */
    public function resolve(array $issue, int $start): ?array
    {
        $old = $issue['sla_snapshot'] ? json_decode($issue['sla_snapshot'], true, 32, JSON_THROW_ON_ERROR) : null;
        $identity = [(int) $issue['member_id'], (int) $issue['service_id'], (int) $issue['sla_override_id'], (string) $issue['priority']];
        if ($old && ($old['identity'] ?? null) === $identity) return $old;
        $contract = false;
        if (!$issue['sla_override_id'] && $issue['member_id']) {
            $contract = $this->db->fetchAssociative('SELECT * FROM tl_issue_sla_contract WHERE member_id=? AND (service_id=? OR service_id=0) AND published=1 AND starts_at<=? AND (ends_at=0 OR ends_at>?) ORDER BY service_id DESC, starts_at DESC, id DESC LIMIT 1', [$issue['member_id'], $issue['service_id'], $start, $start]);
        }
        $id = (int) ($issue['sla_override_id'] ?: ($contract['sla_id'] ?? $this->db->fetchOne('SELECT sla_id FROM tl_issue_service WHERE id=?', [$issue['service_id']])));
        if (!$id) return null;
        // Preserve legacy snapshots until an explicit reassignment.
        if ($old && !isset($old['identity']) && (int) $issue['sla_id'] === $id) return $old + ['sla_id' => $id, 'identity' => $identity];
        $definition = $this->db->fetchAssociative('SELECT * FROM tl_issue_sla WHERE id=? AND published=1', [$id]);
        if (!$definition) return null;
        $calendarId = (int) (($contract['calendar_id'] ?? 0) ?: ($definition['calendar_id'] ?? 0));
        $calendar = $calendarId ? $this->db->fetchAssociative('SELECT * FROM tl_issue_sla_calendar WHERE id=? AND published=1', [$calendarId]) : $definition;
        if (!$calendar) throw new \DomainException('Der zugeordnete Supportkalender ist nicht aktiv.');
        $hours = !empty($calendar['always_open']) ? array_fill_keys(range(1, 7), [['00:00','24:00']]) : json_decode($calendar['business_hours'], true, 32, JSON_THROW_ON_ERROR);
        $holidays = json_decode($calendar['holidays'] ?: '[]', true, 32, JSON_THROW_ON_ERROR);
        $maintenance = json_decode($calendar['maintenance'] ?? '[]', true, 32, JSON_THROW_ON_ERROR) ?: [];
        $this->calendar->validate($calendar['timezone'], $hours, $holidays);
        $this->calendar->validateMaintenance($maintenance);
        $priority = $this->db->fetchAssociative('SELECT response_minutes,resolve_minutes FROM tl_issue_sla_priority WHERE sla_id=? AND priority=? AND published=1', [$id, $issue['priority']]);
        $durations = $priority ?: $definition;
        $snapshot = ['sla_id' => $id, 'identity' => $identity, 'contract_id' => (int) ($contract['id'] ?? 0),
            'contract_number' => $contract['contract_number'] ?? '', 'calendar_id' => $calendarId,
            'country' => $calendar['country'] ?? '', 'region' => $calendar['region'] ?? '',
            'timezone' => $calendar['timezone'], 'hours' => $hours, 'holidays' => $holidays, 'maintenance' => $maintenance,
            'response' => (int) $durations['response_minutes'] * 60, 'resolve' => (int) $durations['resolve_minutes'] * 60];
        if ($snapshot['response'] < 1 || $snapshot['resolve'] < 1) throw new \DomainException('SLA-Zeiten müssen positiv sein.');
        return $snapshot;
    }
}
