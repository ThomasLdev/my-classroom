<?php

declare(strict_types=1);

namespace App\Scheduling\Infrastructure\Story;

use App\Scheduling\Infrastructure\Factory\ClassroomFactory;
use App\Scheduling\Infrastructure\Factory\TimetableSlotFactory;
use Zenstruck\Foundry\Story;

/**
 * A deterministic weekly timetable used as fixtures, so the calendar shows
 * predictable occurrences across the week (Mon–Fri).
 */
final class DefaultTimetableStory extends Story
{
    public function build(): void
    {
        $classe5B = ClassroomFactory::createOne(['name' => '5e B']);
        $classe4A = ClassroomFactory::createOne(['name' => '4e A']);

        // [classroom, dayOfWeek (1=Mon), startMinute, endMinute]
        $grid = [
            [$classe5B, 1, 8 * 60, 9 * 60],
            [$classe5B, 1, 9 * 60, 10 * 60],
            [$classe4A, 1, 10 * 60, 11 * 60],
            [$classe5B, 2, 8 * 60, 9 * 60],
            [$classe4A, 2, 14 * 60, 15 * 60],
            [$classe5B, 3, 9 * 60, 10 * 60],
            [$classe4A, 4, 10 * 60, 11 * 60],
            [$classe5B, 5, 8 * 60, 9 * 60],
            [$classe4A, 5, 9 * 60, 10 * 60],
        ];

        foreach ($grid as [$classroom, $dayOfWeek, $start, $end]) {
            TimetableSlotFactory::createOne([
                'classroom' => $classroom,
                'dayOfWeek' => $dayOfWeek,
                'startMinute' => $start,
                'endMinute' => $end,
            ]);
        }
    }
}
