<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\Tests\Unit\EventListener;

use Diversworld\ContaoIssueServiceBundle\Application\Sla\BusinessCalendar;
use Diversworld\ContaoIssueServiceBundle\EventListener\DataContainer\BusinessHoursCallbacks;
use PHPUnit\Framework\TestCase;

final class BusinessHoursCallbacksTest extends TestCase
{
    public function testExistingHoursSurviveEditingIncludingBreaksAndMidnight(): void
    {
        $callback = new BusinessHoursCallbacks(new BusinessCalendar());
        $json = '{"1":[["09:00","12:00"],["13:00","17:00"]],"7":[["18:00","24:00"]]}';
        self::assertSame($json, $callback->save(serialize($callback->load($json))));
    }

    public function testRowsAreSortedByDayAndStart(): void
    {
        $callback = new BusinessHoursCallbacks(new BusinessCalendar());
        self::assertSame('{"1":[["09:00","12:00"],["13:00","17:00"]],"2":[["09:00","17:00"]]}', $callback->save([
            ['day' => '2', 'from' => '09:00', 'to' => '17:00'],
            ['day' => '1', 'from' => '13:00', 'to' => '17:00'],
            ['day' => '1', 'from' => '09:00', 'to' => '12:00'],
        ]));
    }

    public function testInvalidSchedulesAreRejected(): void
    {
        $callback = new BusinessHoursCallbacks(new BusinessCalendar());
        foreach ([
            [],
            [['day' => '8', 'from' => '09:00', 'to' => '17:00']],
            [['day' => '1', 'from' => '', 'to' => '17:00']],
            [['day' => '1', 'from' => '17:00', 'to' => '09:00']],
            [['day' => '1', 'from' => '09:00', 'to' => '25:00']],
            [['day' => '1', 'from' => '09:00', 'to' => '13:00'], ['day' => '1', 'from' => '12:00', 'to' => '17:00']],
        ] as $rows) {
            try {
                $callback->save(serialize($rows));
                self::fail('Invalid schedule accepted.');
            } catch (\InvalidArgumentException|\DomainException $exception) {
                self::assertNotEmpty($exception->getMessage());
            }
        }
    }
}
