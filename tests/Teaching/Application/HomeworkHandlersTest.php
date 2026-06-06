<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Application;

use App\Teaching\Application\Command\SetHomeworkChecked\SetHomeworkChecked;
use App\Teaching\Application\Command\SetHomeworkChecked\SetHomeworkCheckedHandler;
use App\Teaching\Application\Command\SetSessionHomework\SetSessionHomework;
use App\Teaching\Application\Command\SetSessionHomework\SetSessionHomeworkHandler;
use App\Tests\Support\InMemoryOccurrenceProvider;
use App\Tests\Support\InMemorySessionRepository;
use App\Tests\Support\OccurrenceMother;
use App\Tests\Support\SequentialIdGenerator;
use PHPUnit\Framework\TestCase;

final class HomeworkHandlersTest extends TestCase
{
    private InMemorySessionRepository $sessions;
    private InMemoryOccurrenceProvider $occurrences;

    protected function setUp(): void
    {
        $this->sessions = new InMemorySessionRepository();
        $this->occurrences = new InMemoryOccurrenceProvider();
        $this->occurrences->add(OccurrenceMother::create('slot-1', 'class-1', '2026-06-08', '09:00', '10:00'));
    }

    public function testItMaterialisesTheSessionAndStoresHomework(): void
    {
        $this->setHomework('Exercices 4 et 5');

        $sessions = $this->sessions->all();
        self::assertCount(1, $sessions);
        self::assertSame('Exercices 4 et 5', $sessions[0]->homework);
    }

    public function testItDoesNotMaterialiseForEmptyHomework(): void
    {
        $this->setHomework('   ');

        self::assertSame([], $this->sessions->all());
    }

    public function testItMarksHomeworkAsChecked(): void
    {
        $this->setHomework('Lire le chapitre 7');

        $check = new SetHomeworkCheckedHandler($this->sessions);
        $check(new SetHomeworkChecked('slot-1', '2026-06-08', true));

        self::assertTrue($this->sessions->all()[0]->homeworkChecked);
    }

    private function setHomework(string $homework): void
    {
        $handler = new SetSessionHomeworkHandler($this->sessions, $this->occurrences, new SequentialIdGenerator('hw'));
        $handler(new SetSessionHomework('slot-1', '2026-06-08', $homework));
    }
}
