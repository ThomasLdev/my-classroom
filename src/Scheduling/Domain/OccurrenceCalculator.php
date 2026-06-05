<?php

declare(strict_types=1);

namespace App\Scheduling\Domain;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Occurrence;

/**
 * Pure domain service: expands recurring timetable slots into concrete
 * occurrences. No persistence, no clock — fully deterministic and testable.
 */
final class OccurrenceCalculator
{
    /**
     * @param list<ScheduledSlot> $slots
     *
     * @return list<Occurrence>
     */
    public function forDay(array $slots, \DateTimeImmutable $date): array
    {
        $day = $date->setTime(0, 0);

        $occurrences = [];
        foreach ($slots as $slot) {
            if ($slot->occursOn($day)) {
                $occurrences[] = $slot->toOccurrence($day);
            }
        }

        return $this->sortByStart($occurrences);
    }

    /**
     * @param list<ScheduledSlot> $slots
     */
    public function resolve(array $slots, SlotId $slotId, \DateTimeImmutable $date): ?Occurrence
    {
        $day = $date->setTime(0, 0);

        foreach ($slots as $slot) {
            if ($slot->slotId->equals($slotId) && $slot->occursOn($day)) {
                return $slot->toOccurrence($day);
            }
        }

        return null;
    }

    /**
     * Next occurrence of the same classroom strictly after the given instant,
     * scanning forward day by day up to a bounded horizon.
     *
     * @param list<ScheduledSlot> $slots
     */
    public function nextAfter(
        array $slots,
        ClassroomId $classroomId,
        \DateTimeImmutable $after,
        int $horizonDays = 30,
    ): ?Occurrence {
        $classroomSlots = array_values(array_filter(
            $slots,
            static fn (ScheduledSlot $slot): bool => $slot->classroomId->equals($classroomId),
        ));

        if ($classroomSlots === []) {
            return null;
        }

        $start = $after->setTime(0, 0);
        for ($offset = 0; $offset <= $horizonDays; ++$offset) {
            $day = $start->modify(sprintf('+%d days', $offset));

            foreach ($this->forDay($classroomSlots, $day) as $occurrence) {
                if ($occurrence->timeRange->startsOn($occurrence->date) > $after) {
                    return $occurrence;
                }
            }
        }

        return null;
    }

    /**
     * @param list<Occurrence> $occurrences
     *
     * @return list<Occurrence>
     */
    private function sortByStart(array $occurrences): array
    {
        usort(
            $occurrences,
            static fn (Occurrence $a, Occurrence $b): int => $a->timeRange->startMinute <=> $b->timeRange->startMinute,
        );

        return $occurrences;
    }
}
