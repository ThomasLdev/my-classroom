<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

/**
 * UX-oriented read model for a single session's detail (activities first).
 * Notes & documents will extend this in later iterations.
 */
final readonly class SessionDetailView
{
    /**
     * @param list<ActivityView> $activities
     */
    public function __construct(
        public string $slotId,
        public string $date,
        public string $classroomName,
        public string $start,
        public string $end,
        public bool $materialized,
        public array $activities,
    ) {
    }
}
