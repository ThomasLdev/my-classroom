<?php

declare(strict_types=1);

namespace App\Tests\Unit\Teaching\Application;

use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSession;
use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSessionHandler;
use App\Teaching\Application\Command\RemoveActivityFromSession\RemoveActivityFromSession;
use App\Teaching\Application\Command\RemoveActivityFromSession\RemoveActivityFromSessionHandler;
use App\Tests\Support\InMemoryOccurrenceProvider;
use App\Tests\Support\InMemorySessionRepository;
use App\Tests\Support\OccurrenceMother;
use App\Tests\Support\SequentialIdGenerator;
use PHPUnit\Framework\TestCase;

final class RemoveActivityFromSessionHandlerTest extends TestCase
{
    public function testItRemovesTheActivity(): void
    {
        $sessions = new InMemorySessionRepository();
        $occurrences = new InMemoryOccurrenceProvider();
        $occurrences->add(OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00'));

        $add = new AddActivityToSessionHandler($sessions, $occurrences, new SequentialIdGenerator('a'));
        $add(new AddActivityToSession('slot-1', '2026-06-08', 'À garder'));
        $add(new AddActivityToSession('slot-1', '2026-06-08', 'À supprimer'));
        $target = (string) $sessions->all()[0]->activities[1]->id;

        $remove = new RemoveActivityFromSessionHandler($sessions);
        $remove(new RemoveActivityFromSession('slot-1', '2026-06-08', $target));

        self::assertSame(1, $sessions->all()[0]->activityCount());
        self::assertSame('À garder', $sessions->all()[0]->activities[0]->title);
    }

    public function testItIgnoresAnUnmaterialisedSession(): void
    {
        $sessions = new InMemorySessionRepository();

        $remove = new RemoveActivityFromSessionHandler($sessions);
        $remove(new RemoveActivityFromSession('ghost', '2026-06-08', 'whatever'));

        self::assertSame([], $sessions->all());
    }
}
