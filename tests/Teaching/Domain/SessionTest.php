<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Domain;

use App\Teaching\Domain\Model\Session\ActivityId;
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

    private function materialize(): Session
    {
        return Session::materialize(
            SessionId::fromString('s-1'),
            OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00'),
        );
    }
}
