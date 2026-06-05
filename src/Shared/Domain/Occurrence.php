<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;

/**
 * A (possibly virtual) occurrence of a timetable slot on a concrete date.
 * Computed by the Scheduling context and consumed by Teaching to materialise
 * sessions lazily. Carries enough denormalised data to render the calendar
 * without touching the Scheduling aggregates.
 */
final readonly class Occurrence
{
    public function __construct(
        public SlotId $slotId,
        public ClassroomId $classroomId,
        public \DateTimeImmutable $date,
        public TimeRange $timeRange,
        public string $classroomName,
    ) {
    }
}
