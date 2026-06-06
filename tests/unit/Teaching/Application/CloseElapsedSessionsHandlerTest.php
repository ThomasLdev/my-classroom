<?php

declare(strict_types=1);

namespace App\Tests\Unit\Teaching\Application;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSession;
use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSessionHandler;
use App\Teaching\Application\Command\CloseElapsedSessions\CloseElapsedSessions;
use App\Teaching\Application\Command\CloseElapsedSessions\CloseElapsedSessionsHandler;
use App\Tests\Support\FixedClock;
use App\Tests\Support\InMemoryOccurrenceProvider;
use App\Tests\Support\InMemorySessionRepository;
use App\Tests\Support\OccurrenceMother;
use App\Tests\Support\SequentialIdGenerator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CloseElapsedSessionsHandlerTest extends TestCase
{
    private InMemorySessionRepository $sessions;

    private InMemoryOccurrenceProvider $occurrences;

    private AddActivityToSessionHandler $add;

    protected function setUp(): void
    {
        $this->sessions = new InMemorySessionRepository();
        $this->occurrences = new InMemoryOccurrenceProvider();
        $this->add = new AddActivityToSessionHandler($this->sessions, $this->occurrences, new SequentialIdGenerator('add'));
    }

    public function testItCarriesUnfinishedActivitiesToTheNextOccurrence(): void
    {
        $this->occurrences->add(OccurrenceMother::create('slot-mon', 'class-1', '2026-06-08', '09:00', '10:00'));
        $this->occurrences->add(OccurrenceMother::create('slot-wed', 'class-1', '2026-06-10', '09:00', '10:00'));

        ($this->add)(new AddActivityToSession('slot-mon', '2026-06-08', 'À finir'));
        ($this->add)(new AddActivityToSession('slot-mon', '2026-06-08', 'Déjà fait'));
        $monday = $this->sessions->ofOccurrence(SlotId::fromString('slot-mon'), new DateTimeImmutable('2026-06-08'));
        $monday->activities[1]->markDone();

        $this->runCloseAt('2026-06-08 10:30:00');

        self::assertTrue($monday->isClosed());

        $wednesday = $this->sessions->ofOccurrence(SlotId::fromString('slot-wed'), new DateTimeImmutable('2026-06-10'));
        self::assertNotNull($wednesday, 'The next occurrence has been materialised by the carry-over.');
        self::assertSame(1, $wednesday->activityCount(), 'Only the unfinished activity is carried.');
        self::assertSame('À finir', $wednesday->activities[0]->title);
        self::assertNotNull($wednesday->activities[0]->carriedOverFrom);
    }

    public function testCarryOverIsIdempotentAcrossRuns(): void
    {
        $this->occurrences->add(OccurrenceMother::create('slot-mon', 'class-1', '2026-06-08', '09:00', '10:00'));
        $this->occurrences->add(OccurrenceMother::create('slot-wed', 'class-1', '2026-06-10', '09:00', '10:00'));
        ($this->add)(new AddActivityToSession('slot-mon', '2026-06-08', 'À finir'));

        $this->runCloseAt('2026-06-08 10:30:00');
        $this->runCloseAt('2026-06-08 10:35:00');

        $wednesday = $this->sessions->ofOccurrence(SlotId::fromString('slot-wed'), new DateTimeImmutable('2026-06-10'));
        self::assertSame(1, $wednesday->activityCount(), 'A second run must not duplicate the carried activity.');
    }

    public function testItCascadesThroughConsecutiveElapsedSessions(): void
    {
        // Three back-to-back lessons of the same class, all already elapsed.
        $this->occurrences->add(OccurrenceMother::create('slot-9', 'class-1', '2026-06-08', '09:00', '10:00'));
        $this->occurrences->add(OccurrenceMother::create('slot-10', 'class-1', '2026-06-08', '10:00', '11:00'));
        $this->occurrences->add(OccurrenceMother::create('slot-11', 'class-1', '2026-06-08', '11:00', '12:00'));

        ($this->add)(new AddActivityToSession('slot-9', '2026-06-08', 'Tâche 9h'));
        ($this->add)(new AddActivityToSession('slot-10', '2026-06-08', 'Tâche 10h'));

        $this->runCloseAt('2026-06-08 12:30:00');

        // 9h -> 10h, then 10h (its own + carried) -> 11h. The 11h slot ends up with both.
        $eleven = $this->sessions->ofOccurrence(SlotId::fromString('slot-11'), new DateTimeImmutable('2026-06-08'));
        self::assertNotNull($eleven);
        self::assertSame(2, $eleven->activityCount());
    }

    public function testItKeepsActivitiesWhenNoNextOccurrenceExists(): void
    {
        $this->occurrences->add(OccurrenceMother::create('slot-last', 'class-1', '2026-06-08', '09:00', '10:00'));
        ($this->add)(new AddActivityToSession('slot-last', '2026-06-08', 'Orpheline'));

        $this->runCloseAt('2026-06-08 10:30:00');

        $session = $this->sessions->ofOccurrence(SlotId::fromString('slot-last'), new DateTimeImmutable('2026-06-08'));
        self::assertTrue($session->isClosed());
        self::assertSame(1, $session->activityCount(), 'With nowhere to carry, the activity stays as history.');
    }

    private function runCloseAt(string $iso): void
    {
        $handler = new CloseElapsedSessionsHandler(
            $this->sessions,
            $this->occurrences,
            new SequentialIdGenerator('carry'),
            FixedClock::at($iso),
        );

        $handler(new CloseElapsedSessions());
    }
}
