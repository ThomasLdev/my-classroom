<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetWeek;

use App\Shared\Domain\Occurrence;
use DateTimeImmutable;

final class WeekDayViewFactory
{
    /**
     * @param list<Occurrence> $occurrences the day's occurrences (any order)
     */
    public function create(DateTimeImmutable $date, array $occurrences, bool $hasEvent): WeekDayView
    {
        $classroomNames = [];
        foreach ($occurrences as $occurrence) {
            if (! in_array($occurrence->classroomName, $classroomNames, true)) {
                $classroomNames[] = $occurrence->classroomName;
            }
        }

        return new WeekDayView(
            date: $date->format('Y-m-d'),
            classroomNames: $classroomNames,
            hasEvent: $hasEvent,
        );
    }
}
