<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\MarkActivityNotDone;

final readonly class MarkActivityNotDone
{
    public function __construct(
        public string $slotId,
        public string $date,
        public string $activityId,
    ) {
    }
}
