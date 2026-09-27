<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Application\Sla;

/** All persisted SLA timestamps are Unix seconds; local business windows handle DST. */
final class BusinessCalendar
{
    /** @param array<int, list<array{string, string}>> $hours
     * @param list<string> $holidays */
    public function validate(string $timezone, array $hours, array $holidays): void
    {
        new \DateTimeZone($timezone);
        $count = 0;
        foreach ($hours as $day => $windows) {
            if (!in_array((int) $day, range(1, 7), true) || !is_array($windows)) throw new \InvalidArgumentException('Ungültiger Wochentag.');
            $last = '00:00';
            foreach ($windows as $window) {
                if (!is_array($window) || count($window) !== 2 || !isset($window[0], $window[1])
                    || !is_string($window[0]) || !is_string($window[1])
                    || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $window[0])
                    || !preg_match('/^(?:(?:[01][0-9]|2[0-3]):[0-5][0-9]|24:00)$/D', $window[1])
                    || $window[0] >= $window[1] || $window[0] < $last) throw new \InvalidArgumentException('Geschäftszeiten müssen aufsteigend und überschneidungsfrei sein.');
                $last = $window[1];
                ++$count;
            }
        }
        if (!$count) throw new \InvalidArgumentException('Mindestens ein Geschäftszeitfenster ist erforderlich.');
        foreach ($holidays as $holiday) {
            if (!is_string($holiday) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $holiday)
                || (new \DateTimeImmutable($holiday))->format('Y-m-d') !== $holiday) throw new \InvalidArgumentException('Feiertage müssen gültige Datumswerte YYYY-MM-DD sein.');
        }
    }

    /** @param array<int, list<array{string, string}>> $hours
     * @param list<string> $holidays */
    public function add(int $start, int $seconds, string $timezone, array $hours, array $holidays): int
    {
        $this->validate($timezone, $hours, $holidays);
        if ($seconds < 0) throw new \InvalidArgumentException('Negative SLA-Dauer.');
        if ($seconds === 0) return $start;
        $day = (new \DateTimeImmutable('@'.$start))->setTimezone(new \DateTimeZone($timezone))->setTime(0, 0);
        for ($i = 0; $i < 3660; ++$i, $day = $day->modify('+1 day')) {
            foreach ($this->windows($day, $hours, $holidays) as [$from, $to]) {
                $from = max($from, $start);
                if ($to <= $from) continue;
                if ($seconds <= $to - $from) return $from + $seconds;
                $seconds -= $to - $from;
            }
        }
        throw new \DomainException('SLA-Frist überschreitet den Berechnungshorizont von zehn Jahren.');
    }

    /** @param array<int, list<array{string, string}>> $hours
     * @param list<string> $holidays */
    public function between(int $start, int $end, string $timezone, array $hours, array $holidays): int
    {
        $this->validate($timezone, $hours, $holidays);
        if ($end < $start) return -$this->between($end, $start, $timezone, $hours, $holidays);
        $day = (new \DateTimeImmutable('@'.$start))->setTimezone(new \DateTimeZone($timezone))->setTime(0, 0);
        $seconds = 0;
        for ($i = 0; $day->getTimestamp() < $end; ++$i, $day = $day->modify('+1 day')) {
            if ($i >= 3660) throw new \DomainException('SLA-Zeitraum überschreitet zehn Jahre.');
            foreach ($this->windows($day, $hours, $holidays) as [$from, $to]) $seconds += max(0, min($to, $end) - max($from, $start));
        }
        return $seconds;
    }

    /** @param array<int, list<array{string, string}>> $hours
     * @param list<string> $holidays
     * @return list<array{int, int}> */
    private function windows(\DateTimeImmutable $day, array $hours, array $holidays): array
    {
        if (in_array($day->format('Y-m-d'), $holidays, true)) return [];
        $result = [];
        foreach ($hours[(int) $day->format('N')] ?? [] as [$from, $to]) {
            $result[] = [(new \DateTimeImmutable($day->format('Y-m-d').' '.$from, $day->getTimezone()))->getTimestamp(),
                (new \DateTimeImmutable($day->format('Y-m-d').' '.$to, $day->getTimezone()))->getTimestamp()];
        }
        return $result;
    }
}
