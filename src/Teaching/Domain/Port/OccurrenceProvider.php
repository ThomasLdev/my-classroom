<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Port;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Occurrence;
use DateTimeImmutable;

interface OccurrenceProvider
{
    /**
     * @return list<Occurrence>
     */
    public function forDay(DateTimeImmutable $date): array;

    public function resolve(SlotId $slotId, DateTimeImmutable $date): ?Occurrence;

    public function nextAfter(ClassroomId $classroomId, DateTimeImmutable $after): ?Occurrence;
}
