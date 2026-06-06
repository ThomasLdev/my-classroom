<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Occurrence;
use App\Teaching\Domain\Port\OccurrenceProvider;
use DateTimeImmutable;

final class InMemoryOccurrenceProvider implements OccurrenceProvider
{
    /**
     * @var list<Occurrence>
     */
    private array $occurrences = [];

    public function add(Occurrence $occurrence): void
    {
        $this->occurrences[] = $occurrence;
    }

    public function forDay(DateTimeImmutable $date): array
    {
        $matches = array_filter(
            $this->occurrences,
            static fn (Occurrence $o): bool => $o->date->format('Y-m-d') === $date->format('Y-m-d'),
        );

        usort(
            $matches,
            static fn (Occurrence $a, Occurrence $b): int => $a->timeRange->startMinute <=> $b->timeRange->startMinute,
        );

        return array_values($matches);
    }

    public function resolve(SlotId $slotId, DateTimeImmutable $date): ?Occurrence
    {
        foreach ($this->occurrences as $occurrence) {
            if ($occurrence->slotId->equals($slotId)
                && $occurrence->date->format('Y-m-d') === $date->format('Y-m-d')
            ) {
                return $occurrence;
            }
        }

        return null;
    }

    public function nextAfter(ClassroomId $classroomId, DateTimeImmutable $after): ?Occurrence
    {
        $candidates = array_filter(
            $this->occurrences,
            static fn (Occurrence $o): bool => $o->classroomId->equals($classroomId)
                && $o->timeRange->startsOn($o->date) >= $after,
        );

        usort(
            $candidates,
            static fn (Occurrence $a, Occurrence $b): int => $a->timeRange->startsOn($a->date) <=> $b->timeRange->startsOn($b->date),
        );

        return $candidates[0] ?? null;
    }
}
