<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Domain;

use App\Teaching\Domain\Exception\ActivityNotFound;
use App\Teaching\Domain\Exception\DocumentNotFound;
use App\Teaching\Domain\Model\Session\ActivityId;
use App\Teaching\Domain\Model\Session\DocumentId;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;
use App\Tests\Support\FixedClock;
use App\Tests\Support\OccurrenceMother;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    public function testMaterializedSessionStartsEmptyAndOpen(): void
    {
        $session = $this->materialize();

        self::assertSame(0, $session->activityCount());
        self::assertFalse($session->isClosed());
    }

    public function testHasElapsedDependsOnTheClock(): void
    {
        $session = $this->materialize(); // 2026-06-08 09:00 -> 10:00

        self::assertFalse($session->hasElapsed(FixedClock::at('2026-06-08 09:59:59')));
        self::assertTrue($session->hasElapsed(FixedClock::at('2026-06-08 10:00:00')));
    }

    public function testCloseReturnsPlannedActivitiesAndIsIdempotent(): void
    {
        $session = $this->materialize();
        $session->addActivity(ActivityId::fromString('a-1'), 'Lire le chapitre 3');
        $done = $session->addActivity(ActivityId::fromString('a-2'), 'Corriger le DM');
        $done->markDone();

        $clock = FixedClock::at('2026-06-08 10:05:00');
        $planned = $session->close($clock);

        self::assertCount(1, $planned);
        self::assertSame('a-1', (string) $planned[0]->id);
        self::assertTrue($session->isClosed());

        // Closing again carries nothing: the operation is idempotent.
        self::assertSame([], $session->close($clock));
    }

    public function testCarriedOverActivityKeepsTraceabilityToItsOrigin(): void
    {
        $source = $this->materialize();
        $origin = $source->addActivity(ActivityId::fromString('a-1'), 'Exposé non terminé');

        $target = $this->materialize();
        $carried = $target->receiveCarriedOver(ActivityId::fromString('a-2'), $origin);

        self::assertSame('Exposé non terminé', $carried->title);
        self::assertTrue($carried->isPlanned());
        self::assertNotNull($carried->carriedOverFrom);
        self::assertSame('a-1', (string) $carried->carriedOverFrom);
    }

    public function testMarkingAnActivityDoneThenBackToNotDone(): void
    {
        $session = $this->materialize();
        $activity = $session->addActivity(ActivityId::fromString('a-1'), 'Corriger le DM');
        self::assertTrue($activity->isPlanned());

        $session->markActivityDone(ActivityId::fromString('a-1'));
        self::assertFalse($activity->isPlanned());
        self::assertSame(1, $session->doneCount());

        $session->markActivityNotDone(ActivityId::fromString('a-1'));
        self::assertTrue($activity->isPlanned());
        self::assertSame(0, $session->doneCount());
    }

    public function testMarkingAnUnknownActivityThrows(): void
    {
        $session = $this->materialize();

        $this->expectException(ActivityNotFound::class);
        $session->markActivityDone(ActivityId::fromString('ghost'));
    }

    public function testRemovingAnActivityDropsItFromTheList(): void
    {
        $session = $this->materialize();
        $session->addActivity(ActivityId::fromString('a-1'), 'Lire le chapitre');
        $session->addActivity(ActivityId::fromString('a-2'), 'Corriger le DM');

        $session->removeActivity(ActivityId::fromString('a-1'));

        self::assertSame(1, $session->activityCount());
        self::assertSame('a-2', (string) $session->activities[0]->id);
    }

    public function testRemovingAnUnknownActivityThrows(): void
    {
        $session = $this->materialize();

        $this->expectException(ActivityNotFound::class);
        $session->removeActivity(ActivityId::fromString('ghost'));
    }

    public function testNoteIsTrimmedAndBlankCollapsesToNull(): void
    {
        $session = $this->materialize();
        self::assertNull($session->note);

        $session->setNote('  Revoir les fractions avec Léa  ');
        self::assertSame('Revoir les fractions avec Léa', $session->note);

        $session->setNote('   ');
        self::assertNull($session->note);
    }

    public function testHomeworkIsTrimmedAndClearingItResetsTheCheck(): void
    {
        $session = $this->materialize();

        $session->setHomework('  Exercices 4 et 5 p.32  ');
        self::assertSame('Exercices 4 et 5 p.32', $session->homework);

        $session->setHomeworkChecked(true);
        self::assertTrue($session->homeworkChecked);

        // Clearing the homework also drops its verified flag.
        $session->setHomework('   ');
        self::assertNull($session->homework);
        self::assertFalse($session->homeworkChecked);
    }

    public function testHomeworkCannotBeCheckedWithoutHomework(): void
    {
        $session = $this->materialize();

        $session->setHomeworkChecked(true);
        self::assertFalse($session->homeworkChecked);
    }

    public function testStateRoundTripPreservesHomework(): void
    {
        $session = $this->materialize();
        $session->setHomework('Lire le chapitre 7');
        $session->setHomeworkChecked(true);

        $restored = Session::fromState($session->toState());

        self::assertSame('Lire le chapitre 7', $restored->homework);
        self::assertTrue($restored->homeworkChecked);
    }

    public function testAttachingAndRemovingDocuments(): void
    {
        $session = $this->materialize();

        $session->attachDocument(DocumentId::fromString('d-1'), 'fiche.pdf', 2048, 'application/pdf');
        $session->attachDocument(DocumentId::fromString('d-2'), 'photo.jpg', 4096, 'image/jpeg');
        self::assertSame(2, $session->documentCount());

        $session->removeDocument(DocumentId::fromString('d-1'));
        self::assertSame(1, $session->documentCount());
        self::assertSame('photo.jpg', $session->documents[0]->name);
    }

    public function testRemovingAnUnknownDocumentThrows(): void
    {
        $session = $this->materialize();

        $this->expectException(DocumentNotFound::class);
        $session->removeDocument(DocumentId::fromString('ghost'));
    }

    public function testStateRoundTripPreservesNoteAndDocuments(): void
    {
        $session = $this->materialize();
        $session->setNote('Séance dense, à alléger');
        $session->attachDocument(DocumentId::fromString('d-1'), 'fiche.pdf', 2048, 'application/pdf');

        $restored = Session::fromState($session->toState());

        self::assertSame('Séance dense, à alléger', $restored->note);
        self::assertSame(1, $restored->documentCount());
        self::assertSame('fiche.pdf', $restored->documents[0]->name);
        self::assertSame(2048, $restored->documents[0]->size);
    }

    private function materialize(): Session
    {
        return Session::materialize(
            SessionId::fromString('s-1'),
            OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00'),
        );
    }
}
