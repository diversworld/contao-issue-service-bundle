<?php

declare(strict_types=1);
namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\Application;

use Diversworld\ContaoIssueServiceBundle\Application\Sla\BusinessCalendar;
use PHPUnit\Framework\TestCase;

final class SlaMaintenanceTest extends TestCase
{
    private function at(string $date): int { return (new \DateTimeImmutable($date, new \DateTimeZone('Europe/Berlin')))->getTimestamp(); }

    public function testFridayExampleAndHoliday(): void
    {
        $calendar = new BusinessCalendar();
        $hours = array_fill_keys(range(1,5), [['09:00','18:00']]);
        self::assertSame($this->at('2026-10-05 16:00'),$calendar->add($this->at('2026-10-02 17:00'),28800,'Europe/Berlin',$hours,[]));
        self::assertSame($this->at('2026-10-06 16:00'),$calendar->add($this->at('2026-10-02 17:00'),28800,'Europe/Berlin',$hours,['2026-10-05']));
    }

    public function testOverlappingMaintenanceIsOnlySubtractedOnce(): void
    {
        $calendar = new BusinessCalendar();
        $hours = array_fill_keys(range(1,5), [['09:00','18:00']]);
        $windows = [[$this->at('2026-10-05 10:00'),$this->at('2026-10-05 12:00')],[$this->at('2026-10-05 11:00'),$this->at('2026-10-05 13:00')]];
        $start = $this->at('2026-10-05 09:00');
        $end = $calendar->add($start,14400,'Europe/Berlin',$hours,[],$windows);
        self::assertSame($this->at('2026-10-05 16:00'),$end);
        self::assertSame(14400,$calendar->between($start,$end,'Europe/Berlin',$hours,[],$windows));
        self::assertSame(-14400,$calendar->between($end,$start,'Europe/Berlin',$hours,[],$windows));
    }

    public function testContinuousSupportAcrossDaylightSaving(): void
    {
        $calendar = new BusinessCalendar();
        $hours = array_fill_keys(range(1,7), [['00:00','24:00']]);
        $start = $this->at('2026-10-25 00:00');
        self::assertSame($start+86400,$calendar->add($start,86400,'Europe/Berlin',$hours,[]));
        self::assertSame(90000,$calendar->between($start,$this->at('2026-10-26 00:00'),'Europe/Berlin',$hours,[]));
    }
}
