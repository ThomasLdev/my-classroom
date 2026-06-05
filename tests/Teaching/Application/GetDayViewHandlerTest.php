<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Application;

use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSession;
use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSessionHandler;
use App\Teaching\Application\Query\GetDayView\GetDayView;
use App\Teaching\Application\Query\GetDayView\GetDayViewHandler;
use App\Tests\Support\InMemoryCalendarEventProvider;
use App\Tests\Support\InMemoryOccurrenceProvider;
use App\Tests\Support\InMemorySessionRepository;
use App\Tests\Support\OccurrenceMother;
use App\Tests\Support\SequentialIdGenerator;
use PHPUnit\Framework\TestCase;

final class GetDayViewHandlerTest extends TestCase
{
    public function testItMergesVirtualOccurrencesWithMaterialisedSessions(): void
    {
        $sessions = new InMemorySessionRepository();
        $occurrences = new InMemoryOccurrenceProvider();
        $occurrences->add(OccurrenceMother::create('slot-am', 'class-1', '2026-06-08', '09:00', '10:00', '5e B'));
        $occurrences->add(OccurrenceMother::create('slot-pm', 'class-2', '2026-06-08', '14:00', '15:00', '4e A'));

        // Materialise only the morning slot with two activities, one done.
        $add = new AddActivityToSessionHandler($sessions, $occurrences, new SequentialIdGenerator('gen'));
        $add(new AddActivityToSession('slot-am', '2026-06-08', 'Activité 1'));
        $add(new AddActivityToSession('slot-am', '2026-06-08', 'Activité 2'));
        $sessions->all()[0]->activities[0]->markDone();

        $handler = new GetDayViewHandler($sessions, $occurrences, new InMemoryCalendarEventProvider());
        $view = $handler(new GetDayView('2026-06-08'));

        self::assertSame('2026-06-08', $view->date);
        self::assertCount(2, $view->sessions);

        // Ordered by start time: morning first.
        $morning = $view->sessions[0];
        self::assertTrue($morning->materialized);
        self::assertSame('09:00', $morning->start);
        self::assertSame(2, $morning->activityCount);
        self::assertSame(1, $morning->doneCount);

        $afternoon = $view->sessions[1];
        self::assertFalse($afternoon->materialized);
        self::assertNull($afternoon->sessionId);
        self::assertSame(0, $afternoon->activityCount);
    }
}
