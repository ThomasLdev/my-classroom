<?php

declare(strict_types=1);

namespace App\Tests\Unit\Teaching\Application;

use App\Teaching\Application\Query\GetDayView\EventView;
use App\Teaching\Application\Query\GetWeek\GetWeek;
use App\Teaching\Application\Query\GetWeek\GetWeekHandler;
use App\Teaching\Application\Query\GetWeek\WeekDayViewFactory;
use App\Tests\Support\InMemoryCalendarEventProvider;
use App\Tests\Support\InMemoryOccurrenceProvider;
use App\Tests\Support\OccurrenceMother;
use PHPUnit\Framework\TestCase;

final class GetWeekHandlerTest extends TestCase
{
    public function testItReturnsSevenDaysWithDistinctClassroomsPerDay(): void
    {
        $occurrences = new InMemoryOccurrenceProvider();
        // 2026-06-08 is a Monday: two slots of the same classroom must collapse to one dot.
        $occurrences->add(OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '08:00', '09:00', '5e B'));
        $occurrences->add(OccurrenceMother::create('slot-2', 'class-1', '2026-06-08', '09:00', '10:00', '5e B'));
        $occurrences->add(OccurrenceMother::create('slot-3', 'class-2', '2026-06-10', '10:00', '11:00', '4e A'));

        $event = new EventView('e1', 'Conseil de classe', 'meeting', '2026-06-09 17:00', null, false);
        $handler = new GetWeekHandler($occurrences, new InMemoryCalendarEventProvider([$event]), new WeekDayViewFactory());

        $week = $handler(new GetWeek('2026-06-08'));

        self::assertCount(7, $week->days);

        self::assertSame('2026-06-08', $week->days[0]->date);
        self::assertSame(['5e B'], $week->days[0]->classroomNames);
        self::assertFalse($week->days[0]->hasEvent);

        self::assertTrue($week->days[1]->hasEvent);
        self::assertSame([], $week->days[1]->classroomNames);

        self::assertSame(['4e A'], $week->days[2]->classroomNames);
    }

    public function testItAnchorsOnMondayWhateverTheGivenWeekday(): void
    {
        $occurrences = new InMemoryOccurrenceProvider();
        $handler = new GetWeekHandler($occurrences, new InMemoryCalendarEventProvider(), new WeekDayViewFactory());

        // 2026-06-11 is a Thursday; the week still starts on Monday 2026-06-08.
        $week = $handler(new GetWeek('2026-06-11'));

        self::assertSame('2026-06-08', $week->days[0]->date);
        self::assertSame('2026-06-14', $week->days[6]->date);
    }
}
