<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

final readonly class SessionDetailView
{
    /**
     * @param list<ActivityView> $activities
     * @param list<DocumentView> $documents
     */
    public function __construct(
        public string $slotId,
        public string $date,
        public string $classroomName,
        public string $subject,
        public ?string $room,
        public string $start,
        public string $end,
        public bool $materialized,
        public array $activities,
        public ?string $note,
        public array $documents,
    ) {
    }
}
