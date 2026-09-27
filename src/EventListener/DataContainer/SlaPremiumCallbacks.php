<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Diversworld\ContaoIssueServiceBundle\Application\Sla\BusinessCalendar;
use Doctrine\DBAL\Connection;

final class SlaPremiumCallbacks
{
    public function __construct(private readonly Connection $db) {}

    #[AsCallback(table: 'tl_issue_sla_calendar', target: 'fields.maintenance.save')]
    public function maintenance(mixed $value): string
    {
        $rows = StringUtil::deserialize($value, true);
        if (!is_array($rows) || !array_is_list($rows)) throw new \DomainException('Wartungsfenster müssen eine JSON-Liste sein.');
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !isset($row['from'], $row['to'])) throw new \DomainException('Pro Wartungsfenster Beginn und Ende angeben.');
            $timestamps = [];
            foreach ([$row['from'], $row['to']] as $date) {
                if (is_int($date)) { $timestamps[] = $date; continue; }
                if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/D', $date)) throw new \DomainException('ISO-8601 mit Zeitzone erforderlich, z. B. 2026-10-01T09:00:00+02:00.');
                $parsed = new \DateTimeImmutable($date);
                if (\DateTimeImmutable::getLastErrors() !== false) throw new \DomainException('Ungültiges Wartungsdatum.');
                $timestamps[] = $parsed->getTimestamp();
            }
            if ($timestamps[0] >= $timestamps[1]) throw new \DomainException('Wartungsende muss nach dem Beginn liegen.');
            $result[] = $timestamps;
        }
        return json_encode($result, JSON_THROW_ON_ERROR);
    }

    /** @return list<array{from: string, to: string}> */
    #[AsCallback(table: 'tl_issue_sla_calendar', target: 'fields.maintenance.load')]
    public function loadMaintenance(mixed $value): array
    {
        $rows = json_decode((string) ($value ?: '[]'), true, 32, JSON_THROW_ON_ERROR);
        return array_map(static fn (array $row): array => ['from' => gmdate('Y-m-d\TH:i:s\Z', $row[0]), 'to' => gmdate('Y-m-d\TH:i:s\Z', $row[1])], array_values($rows));
    }

    /** @return list<array{date: string}> */
    #[AsCallback(table: 'tl_issue_sla_calendar', target: 'fields.holidays.load')]
    public function loadHolidays(mixed $value): array
    {
        return array_map(static fn (string $date): array => ['date' => $date], array_values(json_decode((string) ($value ?: '[]'), true, 32, JSON_THROW_ON_ERROR)));
    }

    #[AsCallback(table: 'tl_issue_sla_calendar', target: 'fields.holidays.save')]
    public function holidays(mixed $value): string
    {
        $dates = [];
        foreach (StringUtil::deserialize($value, true) as $row) {
            if (!is_array($row) || !is_string($row['date'] ?? null)) throw new \DomainException('Datum erforderlich.');
            $dates[] = trim($row['date']);
        }
        (new BusinessCalendar())->validate('UTC', [1 => [['09:00','17:00']]], $dates);
        return json_encode(array_values(array_unique($dates)), JSON_THROW_ON_ERROR);
    }

    #[AsCallback(table: 'tl_issue_sla', target: 'fields.calendar_id.save')]
    #[AsCallback(table: 'tl_issue_sla_contract', target: 'fields.calendar_id.save')]
    public function calendar(mixed $value): int
    {
        $id = (int) $value;
        if ($id && !$this->db->fetchOne('SELECT id FROM tl_issue_sla_calendar WHERE id=? AND published=1', [$id])) throw new \DomainException('Bitte einen aktiven Supportkalender wählen.');
        return $id;
    }

    #[AsCallback(table: 'tl_issue_sla_escalation', target: 'fields.target_status_id.save')]
    public function targetStatus(mixed $value): int
    {
        $id = (int) $value;
        if ($id && !$this->db->fetchOne('SELECT id FROM tl_issue_status WHERE id=? AND published=1 AND is_resolved=0 AND is_closed=0', [$id])) throw new \DomainException('Eskalationen benötigen einen aktiven, offenen Zielstatus.');
        return $id;
    }

    #[AsCallback(table: 'tl_issue_sla_escalation', target: 'fields.webhook_url.save')]
    public function webhook(mixed $value): string
    {
        $url = trim((string) $value);
        if ($url === '') return '';
        if (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null) throw new \DomainException('Webhook benötigt eine HTTPS-URL ohne Zugangsdaten.');
        return $url;
    }

    #[AsCallback(table: 'tl_issue_sla_contract', target: 'fields.published.save')]
    public function contract(mixed $value, DataContainer $dc): mixed
    {
        if (!$value) return $value;
        $row = $this->db->fetchAssociative('SELECT * FROM tl_issue_sla_contract WHERE id=?', [$dc->id]);
        if (!$row || !$row['member_id'] || !$row['sla_id'] || !$row['starts_at'] || trim($row['contract_number']) === '') throw new \DomainException('Kunde, SLA, Vertragsnummer und Beginn sind erforderlich.');
        if ($row['ends_at'] && $row['ends_at'] <= $row['starts_at']) throw new \DomainException('Vertragsende muss nach dem Beginn liegen.');
        if (!$this->db->fetchOne('SELECT id FROM tl_issue_sla WHERE id=? AND published=1', [$row['sla_id']])) throw new \DomainException('Eine aktive SLA-Definition ist erforderlich.');
        if ($this->db->fetchOne('SELECT id FROM tl_issue_sla_contract WHERE id<>? AND member_id=? AND service_id=? AND published=1 AND (ends_at=0 OR ends_at>?) AND (?=0 OR starts_at<?)', [$dc->id, $row['member_id'], $row['service_id'], $row['starts_at'], $row['ends_at'], $row['ends_at']])) throw new \DomainException('Für diesen Kunden und Service besteht bereits ein überlappender Vertrag.');
        return $value;
    }
}
