<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Application;

use App\Teaching\Application\Command\AttachDocumentToSession\AttachDocumentToSession;
use App\Teaching\Application\Command\AttachDocumentToSession\AttachDocumentToSessionHandler;
use App\Teaching\Application\Command\RemoveDocumentFromSession\RemoveDocumentFromSession;
use App\Teaching\Application\Command\RemoveDocumentFromSession\RemoveDocumentFromSessionHandler;
use App\Tests\Support\InMemoryDocumentStorage;
use App\Tests\Support\InMemoryOccurrenceProvider;
use App\Tests\Support\InMemorySessionRepository;
use App\Tests\Support\OccurrenceMother;
use App\Tests\Support\SequentialIdGenerator;
use PHPUnit\Framework\TestCase;

final class DocumentHandlersTest extends TestCase
{
    private InMemorySessionRepository $sessions;
    private InMemoryDocumentStorage $storage;
    private InMemoryOccurrenceProvider $occurrences;

    protected function setUp(): void
    {
        $this->sessions = new InMemorySessionRepository();
        $this->storage = new InMemoryDocumentStorage();
        $this->occurrences = new InMemoryOccurrenceProvider();
        $this->occurrences->add(OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00'));
    }

    public function testAttachingMaterialisesTheSessionAndStoresTheBytes(): void
    {
        $this->attach('fiche.pdf');

        $sessions = $this->sessions->all();
        self::assertCount(1, $sessions);
        self::assertCount(1, $sessions[0]->documents);
        self::assertSame('fiche.pdf', $sessions[0]->documents[0]->name);
        self::assertSame(1, $this->storage->count());
    }

    public function testRemovingDropsTheDocumentAndTheStoredBytes(): void
    {
        $this->attach('fiche.pdf');
        $documentId = (string) $this->sessions->all()[0]->documents[0]->id;

        $remove = new RemoveDocumentFromSessionHandler($this->sessions, $this->storage);
        $remove(new RemoveDocumentFromSession('slot-1', '2026-06-08', $documentId));

        self::assertCount(0, $this->sessions->all()[0]->documents);
        self::assertFalse($this->storage->has($documentId));
    }

    private function attach(string $name): void
    {
        $handler = new AttachDocumentToSessionHandler(
            $this->sessions,
            $this->occurrences,
            $this->storage,
            new SequentialIdGenerator('gen'),
        );

        $handler(new AttachDocumentToSession('slot-1', '2026-06-08', $name, 2048, 'application/pdf', '/tmp/source'));
    }
}
