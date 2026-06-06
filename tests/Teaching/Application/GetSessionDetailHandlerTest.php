<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Application;

use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSession;
use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSessionHandler;
use App\Teaching\Application\Command\AttachDocumentToSession\AttachDocumentToSession;
use App\Teaching\Application\Command\AttachDocumentToSession\AttachDocumentToSessionHandler;
use App\Teaching\Application\Command\SetSessionHomework\SetSessionHomework;
use App\Teaching\Application\Command\SetSessionHomework\SetSessionHomeworkHandler;
use App\Teaching\Application\Command\SetSessionNote\SetSessionNote;
use App\Teaching\Application\Command\SetSessionNote\SetSessionNoteHandler;
use App\Teaching\Application\Query\GetSessionDetail\ActivityViewFactory;
use App\Teaching\Application\Query\GetSessionDetail\DocumentViewFactory;
use App\Teaching\Application\Query\GetSessionDetail\GetSessionDetail;
use App\Teaching\Application\Query\GetSessionDetail\GetSessionDetailHandler;
use App\Teaching\Application\Query\GetSessionDetail\SessionDetailViewFactory;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use App\Tests\Support\InMemoryDocumentStorage;
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
        $this->handler = new GetSessionDetailHandler(
            $this->sessions,
            $this->occurrences,
            new SessionDetailViewFactory(new ActivityViewFactory(), new DocumentViewFactory()),
        );
    }

    public function testItRecallsTheUncheckedHomeworkOfThePreviousSessionOfTheSameClass(): void
    {
        // Two meetings of class-1: homework given on the 8th, recalled on the 10th.
        $this->occurrences->add(OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00'));
        $this->occurrences->add(OccurrenceMother::create('slot-2', 'class-1', '2026-06-10', '09:00', '10:00'));

        $setHomework = new SetSessionHomeworkHandler($this->sessions, $this->occurrences, new SequentialIdGenerator('hw'));
        $setHomework(new SetSessionHomework('slot-1', '2026-06-08', 'Exercices 4 et 5'));

        $view = ($this->handler)(new GetSessionDetail('slot-2', '2026-06-10'));

        self::assertNotNull($view->previousHomework);
        self::assertSame('Exercices 4 et 5', $view->previousHomework->text);
        self::assertSame('slot-1', $view->previousHomework->slotId);
        self::assertSame('2026-06-08', $view->previousHomework->date);

        // Earlier session itself has no "previous" homework to recall.
        $first = ($this->handler)(new GetSessionDetail('slot-1', '2026-06-08'));
        self::assertNull($first->previousHomework);
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
        self::assertNull($view->note);
        self::assertSame([], $view->documents);
        self::assertSame('4e A', $view->classroomName);
    }

    public function testItSurfacesTheNoteAndDocuments(): void
    {
        $this->occurrences->add(OccurrenceMother::create('slot-2', 'class-1', '2026-06-08', '11:00', '12:00'));

        $setNote = new SetSessionNoteHandler($this->sessions, $this->occurrences, new SequentialIdGenerator('n'));
        $setNote(new SetSessionNote('slot-2', '2026-06-08', 'Reprendre la dictée'));

        $attach = new AttachDocumentToSessionHandler(
            $this->sessions,
            $this->occurrences,
            new InMemoryDocumentStorage(),
            new SequentialIdGenerator('d'),
        );
        $attach(new AttachDocumentToSession('slot-2', '2026-06-08', 'dictee.pdf', 2048, 'application/pdf', '/tmp/x'));

        $view = ($this->handler)(new GetSessionDetail('slot-2', '2026-06-08'));

        self::assertSame('Reprendre la dictée', $view->note);
        self::assertCount(1, $view->documents);
        self::assertSame('dictee.pdf', $view->documents[0]->name);
        self::assertSame('dictee.pdf', $view->documents[0]->displayName);
        self::assertSame('2 Ko', $view->documents[0]->sizeLabel);
    }

    public function testItTruncatesLongDocumentNamesKeepingTheExtension(): void
    {
        $this->occurrences->add(OccurrenceMother::create('slot-3', 'class-1', '2026-06-08', '15:00', '16:00'));

        $attach = new AttachDocumentToSessionHandler(
            $this->sessions,
            $this->occurrences,
            new InMemoryDocumentStorage(),
            new SequentialIdGenerator('d'),
        );
        $longName = 'exercices-fractions-niveau-5eme-avec-corrige-detaille-version-finale.pdf';
        $attach(new AttachDocumentToSession('slot-3', '2026-06-08', $longName, 2048, 'application/pdf', '/tmp/x'));

        $document = ($this->handler)(new GetSessionDetail('slot-3', '2026-06-08'))->documents[0];

        self::assertSame($longName, $document->name);
        self::assertLessThanOrEqual(35, mb_strlen($document->displayName));
        self::assertStringEndsWith('.pdf', $document->displayName);
        self::assertStringContainsString('…', $document->displayName);
    }

    public function testItRejectsAnUnscheduledSlot(): void
    {
        $this->expectException(SlotNotScheduled::class);

        ($this->handler)(new GetSessionDetail('ghost', '2026-06-08'));
    }
}
