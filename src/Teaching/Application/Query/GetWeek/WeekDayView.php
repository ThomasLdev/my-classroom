<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetWeek;

final readonly class WeekDayView
{
    /**
     * @param list<string> $classroomNames distinct classrooms with an occurrence that day, in chronological order
     */
    public function __construct(
        public string $date,
        public array $classroomNames,
        public bool $hasEvent,
    ) {
    }
}
