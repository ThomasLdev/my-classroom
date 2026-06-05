<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetDayView;

// materialized=false means a still-virtual occurrence (no row yet) and sessionId is null.
final readonly class SessionView
{
    public function __construct(
        public string $slotId,
        public ?string $sessionId,
        public string $classroomName,
        public string $subject,
        public ?string $room,
        public string $start,
        public string $end,
        public int $activityCount,
        public int $doneCount,
        public bool $cancelled,
        public bool $materialized,
    ) {
    }
}
