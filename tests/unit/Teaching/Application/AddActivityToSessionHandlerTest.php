<?php

declare(strict_types=1);

namespace App\Tests\Unit\Teaching\Application;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSession;
use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSessionHandler;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use App\Tests\Support\InMemoryOccurrenceProvider;
use App\Tests\Support\InMemorySessionRepository;
use App\Tests\Support\OccurrenceMother;
use App\Tests\Support\SequentialIdGenerator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AddActivityToSessionHandlerTest extends TestCase
{
    private InMemorySessionRepository $sessions;

    private InMemoryOccurrenceProvider $occurrences;

    private AddActivityToSessionHandler $handler;

    protected function setUp(): void
    {
        $this->sessions = new InMemorySessionRepository();
        $this->occurrences = new InMemoryOccurrenceProvider();
        $this->handler = new AddActivityToSessionHandler(
            $this->sessions,
            $this->occurrences,
            new SequentialIdGenerator('gen'),
        );
    }

    public function testItMaterialisesAVirtualSessionOnFirstActivity(): void
    {
        $this->occurrences->add(OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00'));

        self::assertNull($this->sessions->ofOccurrence(SlotId::fromString('slot-1'), new DateTimeImmutable('2026-06-08')));

        ($this->handler)(new AddActivityToSession('slot-1', '2026-06-08', 'Distribuer le contrôle'));

        $session = $this->sessions->ofOccurrence(SlotId::fromString('slot-1'), new DateTimeImmutable('2026-06-08'));
        self::assertNotNull($session);
        self::assertSame(1, $session->activityCount());
        self::assertSame('Distribuer le contrôle', $session->activities[0]->title);
    }

    public function testItReusesAnAlreadyMaterialisedSession(): void
    {
        $this->occurrences->add(OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00'));

        ($this->handler)(new AddActivityToSession('slot-1', '2026-06-08', 'Activité 1'));
        ($this->handler)(new AddActivityToSession('slot-1', '2026-06-08', 'Activité 2'));

        self::assertCount(1, $this->sessions->all());
        self::assertSame(2, $this->sessions->all()[0]->activityCount());
    }

    public function testItRejectsAnActivityOnAnUnscheduledSlot(): void
    {
        $this->expectException(SlotNotScheduled::class);

        ($this->handler)(new AddActivityToSession('ghost-slot', '2026-06-08', 'Nope'));
    }
}
