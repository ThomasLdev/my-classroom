<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Application;

use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSession;
use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSessionHandler;
use App\Teaching\Application\Query\GetSessionDetail\GetSessionDetail;
use App\Teaching\Application\Query\GetSessionDetail\GetSessionDetailHandler;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use App\Tests\Support\InMemoryOccurrenceProvider;
use App\Tests\Support\InMemorySessionRepository;
use App\Tests\Support\OccurrenceMother;
use App\Tests\Support\SequentialIdGenerator;
use PHPUnit\Framework\TestCase;

final class GetSessionDetailHandlerTest extends TestCase
{
    private InMemorySessionRepository $sessions;
    private InMemoryOccurrenceProvider $occurrences;
    private GetSessionDetailHandler $handler;

    protected function setUp(): void
    {
        $this->sessions = new InMemorySessionRepository();
        $this->occurrences = new InMemoryOccurrenceProvider();
        $this->handler = new GetSessionDetailHandler($this->sessions, $this->occurrences);
    }

    public function testItReturnsTheMaterialisedSessionWithItsActivities(): void
    {
        $this->occurrences->add(OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00', '5e B', 'Français', '214'));

        $add = new AddActivityToSessionHandler($this->sessions, $this->occurrences, new SequentialIdGenerator('gen'));
        $add(new AddActivityToSession('slot-1', '2026-06-08', 'Activité 1'));
        $add(new AddActivityToSession('slot-1', '2026-06-08', 'Activité 2'));
        $first = (string) $this->sessions->all()[0]->activities[0]->id;

        // Mark the first activity done through the handler chain is covered elsewhere;
        // here we just read it back.
        $view = ($this->handler)(new GetSessionDetail('slot-1', '2026-06-08'));

        self::assertTrue($view->materialized);
        self::assertSame('5e B', $view->classroomName);
        self::assertSame('Français', $view->subject);
        self::assertSame('214', $view->room);
        self::assertSame('09:00', $view->start);
        self::assertCount(2, $view->activities);
        self::assertSame('Activité 1', $view->activities[0]->title);
        self::assertFalse($view->activities[0]->done);
        self::assertFalse($view->activities[0]->carriedOver);
        self::assertNotSame('', $first);
    }

    public function testItReturnsAVirtualSessionWhenNothingIsMaterialised(): void
    {
        $this->occurrences->add(OccurrenceMother::create('slot-9', 'class-1', '2026-06-08', '14:00', '15:00', '4e A'));

        $view = ($this->handler)(new GetSessionDetail('slot-9', '2026-06-08'));

        self::assertFalse($view->materialized);
        self::assertSame([], $view->activities);
        self::assertSame('4e A', $view->classroomName);
    }

    public function testItRejectsAnUnscheduledSlot(): void
    {
        $this->expectException(SlotNotScheduled::class);

        ($this->handler)(new GetSessionDetail('ghost', '2026-06-08'));
    }
}
