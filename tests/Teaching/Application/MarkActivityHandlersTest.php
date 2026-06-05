<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Application;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSession;
use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSessionHandler;
use App\Teaching\Application\Command\MarkActivityDone\MarkActivityDone;
use App\Teaching\Application\Command\MarkActivityDone\MarkActivityDoneHandler;
use App\Teaching\Application\Command\MarkActivityNotDone\MarkActivityNotDone;
use App\Teaching\Application\Command\MarkActivityNotDone\MarkActivityNotDoneHandler;
use App\Teaching\Domain\Exception\SessionNotFound;
use App\Tests\Support\InMemoryOccurrenceProvider;
use App\Tests\Support\InMemorySessionRepository;
use App\Tests\Support\OccurrenceMother;
use App\Tests\Support\SequentialIdGenerator;
use PHPUnit\Framework\TestCase;

final class MarkActivityHandlersTest extends TestCase
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

    public function testItTogglesAnActivityDoneThenNotDone(): void
    {
        $this->occurrences->add(OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00'));
        ($this->add)(new AddActivityToSession('slot-1', '2026-06-08', 'Tâche'));

        $activityId = (string) $this->session()->activities[0]->id;

        (new MarkActivityDoneHandler($this->sessions))(new MarkActivityDone('slot-1', '2026-06-08', $activityId));
        self::assertSame(1, $this->session()->doneCount());

        (new MarkActivityNotDoneHandler($this->sessions))(new MarkActivityNotDone('slot-1', '2026-06-08', $activityId));
        self::assertSame(0, $this->session()->doneCount());
    }

    public function testItRejectsTogglingOnANonMaterialisedSession(): void
    {
        $this->expectException(SessionNotFound::class);

        (new MarkActivityDoneHandler($this->sessions))(new MarkActivityDone('ghost-slot', '2026-06-08', 'whatever'));
    }

    private function session(): \App\Teaching\Domain\Model\Session\Session
    {
        return $this->sessions->ofOccurrence(SlotId::fromString('slot-1'), new \DateTimeImmutable('2026-06-08'))
            ?? throw new \RuntimeException('Session expected.');
    }
}
