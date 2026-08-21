<?php

declare(strict_types=1);

namespace App\Scheduling\Domain;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Occurrence;
use App\Shared\Domain\TimeRange;
use DateTimeImmutable;

final readonly class ScheduledSlot
{
    public function __construct(
        public SlotId $slotId,
        public ClassroomId $classroomId,
        public string $classroomName,
        public DayOfWeek $dayOfWeek,
        public TimeRange $timeRange,
        public string $subject,
        public ?string $room = null,
        public ?DateTimeImmutable $validFrom = null,
        public ?DateTimeImmutable $validTo = null,
    ) {
    }

    public function occursOn(DateTimeImmutable $date): bool
    {
        if (! $this->dayOfWeek->matches($date)) {
            return false;
        }
        if ($this->validFrom instanceof DateTimeImmutable && $date < $this->validFrom->setTime(0, 0)) {
            return false;
        }
        return ! $this->validTo instanceof DateTimeImmutable || $date <= $this->validTo->setTime(0, 0);
    }

    public function toOccurrence(DateTimeImmutable $date): Occurrence
    {
        return new Occurrence(
            $this->slotId,
            $this->classroomId,
            $date->setTime(0, 0),
            $this->timeRange,
            $this->classroomName,
            $this->subject,
            $this->room,
        );
    }
}
