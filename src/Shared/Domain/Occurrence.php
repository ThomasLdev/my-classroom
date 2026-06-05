<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;

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
