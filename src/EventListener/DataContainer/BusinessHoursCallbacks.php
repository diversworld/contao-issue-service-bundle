<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\StringUtil;
use Diversworld\ContaoIssueServiceBundle\Application\Sla\BusinessCalendar;

final class BusinessHoursCallbacks
{
    public function __construct(private readonly BusinessCalendar $calendar) {}

    #[AsCallback(table: 'tl_issue_sla', target: 'fields.business_hours.load')]
    public function load(mixed $value): array
    {
        if ($value === null || $value === '') return [];
        $hours = json_decode((string) $value, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($hours)) throw new \DomainException('Ungültige gespeicherte Geschäftszeiten.');
        $rows = [];
        foreach ($hours as $day => $windows) {
            foreach ($windows as [$from, $to]) {
                $rows[] = ['day' => (string) $day, 'from' => $from, 'to' => $to];
            }
        }
        return $rows;
    }

    #[AsCallback(table: 'tl_issue_sla', target: 'fields.business_hours.save')]
    public function save(mixed $value): string
    {
        // DC_Table serializes compound widget values before invoking save callbacks.
        $rows = StringUtil::deserialize($value, true);
        $hours = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !in_array((string) ($row['day'] ?? ''), ['1','2','3','4','5','6','7'], true)
                || !is_string($row['from'] ?? null) || !is_string($row['to'] ?? null)) {
                throw new \DomainException('Bitte Wochentag, Beginn und Ende für jedes Zeitfenster angeben.');
            }
            $hours[(int) $row['day']][] = [trim($row['from']), trim($row['to'])];
        }
        ksort($hours);
        foreach ($hours as &$windows) {
            usort($windows, static fn (array $a, array $b): int => strcmp($a[0], $b[0]));
        }
        unset($windows);
        $this->calendar->validate('UTC', $hours, []);
        return json_encode($hours, JSON_THROW_ON_ERROR);
    }
}
