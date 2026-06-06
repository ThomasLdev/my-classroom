<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\MarkActivityDone;

final readonly class MarkActivityDone
{
    public function __construct(
        public string $slotId,
        public string $date,
        public string $activityId,
    ) {
    }
}
