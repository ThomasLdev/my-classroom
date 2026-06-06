<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

/**
 * Homework given in a previous session of the same classroom, not yet verified —
 * surfaced as a reminder so the teacher can tick it off.
 */
final readonly class PreviousHomeworkView
{
    public function __construct(
        public string $slotId,
        public string $date,
        public string $dateLabel,
        public string $text,
    ) {
    }
}
