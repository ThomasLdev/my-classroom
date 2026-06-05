<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Occurrence;
use App\Shared\Domain\TimeRange;

/**
 * Test data builder for occurrences, to keep the tests readable.
 */
final class OccurrenceMother
{
    public static function create(
        string $slotId,
        string $classroomId,
        string $date,
        string $start,
        string $end,
        string $classroomName = '5e B',
        string $subject = 'Français',
        ?string $room = '214',
    ): Occurrence {
        return new Occurrence(
            SlotId::fromString($slotId),
            ClassroomId::fromString($classroomId),
            new \DateTimeImmutable($date.' 00:00:00'),
            TimeRange::fromLabels($start, $end),
            $classroomName,
            $subject,
            $room,
        );
    }
}
