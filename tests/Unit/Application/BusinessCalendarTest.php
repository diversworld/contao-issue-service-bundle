<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;
use Diversworld\ContaoIssueServiceBundle\Application\Sla\BusinessCalendar;
use PHPUnit\Framework\TestCase;

final class BusinessCalendarTest extends TestCase
{
    private function at(string $date): int { return (new \DateTimeImmutable($date, new \DateTimeZone('Europe/Berlin')))->getTimestamp(); }
    public function testWeekendHolidayLunchAndRemainingTime(): void
    {
        $calendar = new BusinessCalendar();
        $hours = array_fill_keys(range(1, 5), [['09:00','12:00'], ['13:00','17:00']]);
        $start = $this->at('2026-04-02 16:00'); // Thursday; Friday and Monday are holidays.
        $holidays = ['2026-04-03','2026-04-06'];
        $end = $calendar->add($start, 5 * 3600, 'Europe/Berlin', $hours, $holidays);
        self::assertSame($this->at('2026-04-07 14:00'), $end);
        self::assertSame(5 * 3600, $calendar->between($start, $end, 'Europe/Berlin', $hours, $holidays));
        self::assertSame(-5 * 3600, $calendar->between($end, $start, 'Europe/Berlin', $hours, $holidays));
    }
    public function testDstAndExactClosingBoundary(): void
    {
        $calendar = new BusinessCalendar();
        $hours = array_fill_keys(range(1,7), [['00:00','24:00']]);
        self::assertSame($this->at('2026-03-30 00:00'), $calendar->add($this->at('2026-03-29 00:00'), 23 * 3600, 'Europe/Berlin', $hours, []));
        self::assertSame($this->at('2026-10-26 00:00'), $calendar->add($this->at('2026-10-25 00:00'), 25 * 3600, 'Europe/Berlin', $hours, []));
        self::assertSame($this->at('2026-04-02 17:00'), $calendar->add($this->at('2026-04-02 16:00'), 3600, 'Europe/Berlin', [4 => [['09:00','17:00']]], []));
    }
    public function testEmptyCalendarRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new BusinessCalendar())->add(time(), 1, 'UTC', [], []);
    }
    public function testOverlappingWindowsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new BusinessCalendar())->validate('UTC', [1 => [['09:00','12:00'], ['11:00','15:00']]], []);
    }
    public function testInvalidHolidayRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new BusinessCalendar())->validate('UTC', [1 => [['09:00','12:00']]], ['2026-02-30']);
    }
}
