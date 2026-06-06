<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Application;

use App\Teaching\Application\Command\SetSessionNote\SetSessionNote;
use App\Teaching\Application\Command\SetSessionNote\SetSessionNoteHandler;
use App\Tests\Support\InMemoryOccurrenceProvider;
use App\Tests\Support\InMemorySessionRepository;
use App\Tests\Support\OccurrenceMother;
use App\Tests\Support\SequentialIdGenerator;
use PHPUnit\Framework\TestCase;

final class SetSessionNoteHandlerTest extends TestCase
{
    private InMemorySessionRepository $sessions;
    private SetSessionNoteHandler $handler;

    protected function setUp(): void
    {
        $this->sessions = new InMemorySessionRepository();
        $occurrences = new InMemoryOccurrenceProvider();
        $occurrences->add(OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00'));
        $this->handler = new SetSessionNoteHandler($this->sessions, $occurrences, new SequentialIdGenerator('gen'));
    }

    public function testItMaterialisesTheSessionAndStoresTheNote(): void
    {
        ($this->handler)(new SetSessionNote('slot-1', '2026-06-08', 'Penser à ramasser les copies'));

        $sessions = $this->sessions->all();
        self::assertCount(1, $sessions);
        self::assertSame('Penser à ramasser les copies', $sessions[0]->note);
    }

    public function testItDoesNotMaterialiseAVirtualSessionForAnEmptyNote(): void
    {
        ($this->handler)(new SetSessionNote('slot-1', '2026-06-08', '   '));

        self::assertSame([], $this->sessions->all());
    }

    public function testItClearsTheNoteWhenEmptiedOnAnExistingSession(): void
    {
        ($this->handler)(new SetSessionNote('slot-1', '2026-06-08', 'Un mot'));
        self::assertSame('Un mot', $this->sessions->all()[0]->note);

        // Wiping the textarea must drop the note, not keep an empty one.
        ($this->handler)(new SetSessionNote('slot-1', '2026-06-08', '   '));
        self::assertNull($this->sessions->all()[0]->note);
    }
}
