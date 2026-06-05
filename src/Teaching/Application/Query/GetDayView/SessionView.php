<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetDayView;

/**
 * One slot on the day. `materialized = false` means it is still a virtual
 * occurrence (no row yet); `sessionId` is then null.
 */
final readonly class SessionView
{
    public function __construct(
        public string $slotId,
        public ?string $sessionId,
        public string $classroomName,
        public string $start,
        public string $end,
        public int $activityCount,
        public int $doneCount,
        public bool $cancelled,
        public bool $materialized,
    ) {
    }
}
