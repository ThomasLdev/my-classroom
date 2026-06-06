<?php

declare(strict_types=1);

namespace App\Tests\Unit\Scheduling;

use App\Scheduling\Domain\DayOfWeek;
use App\Scheduling\Domain\OccurrenceCalculator;
use App\Scheduling\Domain\ScheduledSlot;
use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\TimeRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class OccurrenceCalculatorTest extends TestCase
{
    private OccurrenceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new OccurrenceCalculator();
    }

    public function testForDayReturnsMatchingSlotsSortedByStart(): void
    {
        $slots = [
            $this->slot('slot-late', 'class-1', DayOfWeek::Monday, '10:00', '11:00'),
            $this->slot('slot-early', 'class-1', DayOfWeek::Monday, '08:00', '09:00'),
            $this->slot('slot-other', 'class-1', DayOfWeek::Tuesday, '08:00', '09:00'),
        ];

        // 2026-06-08 is a Monday.
        $occurrences = $this->calculator->forDay($slots, new DateTimeImmutable('2026-06-08'));

        self::assertCount(2, $occurrences);
        self::assertSame('08:00', $occurrences[0]->timeRange->startLabel());
        self::assertSame('10:00', $occurrences[1]->timeRange->startLabel());
    }

    public function testResolveReturnsTheOccurrenceForThatSlotAndDate(): void
    {
        $slots = [$this->slot('slot-1', 'class-1', DayOfWeek::Monday, '09:00', '10:00')];

        $found = $this->calculator->resolve($slots, SlotId::fromString('slot-1'), new DateTimeImmutable('2026-06-08'));
        self::assertNotNull($found);

        // 2026-06-09 is a Tuesday -> the Monday slot does not occur.
        $missing = $this->calculator->resolve($slots, SlotId::fromString('slot-1'), new DateTimeImmutable('2026-06-09'));
        self::assertNull($missing);
    }

    public function testNextAfterFindsTheNextOccurrenceOfTheClassroom(): void
    {
        $slots = [
            $this->slot('slot-mon', 'class-1', DayOfWeek::Monday, '09:00', '10:00'),
            $this->slot('slot-wed', 'class-1', DayOfWeek::Wednesday, '09:00', '10:00'),
        ];

        // After Monday 2026-06-08 10:00 -> next is Wednesday 2026-06-10.
        $next = $this->calculator->nextAfter(
            $slots,
            ClassroomId::fromString('class-1'),
            new DateTimeImmutable('2026-06-08 10:00:00'),
        );

        self::assertNotNull($next);
        self::assertSame('2026-06-10', $next->date->format('Y-m-d'));
    }

    private function slot(string $slotId, string $classroomId, DayOfWeek $day, string $start, string $end): ScheduledSlot
    {
        return new ScheduledSlot(
            SlotId::fromString($slotId),
            ClassroomId::fromString($classroomId),
            '5e B',
            $day,
            TimeRange::fromLabels($start, $end),
            'Français',
        );
    }
}
