<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\RemoveActivityFromSession;

final readonly class RemoveActivityFromSession
{
    public function __construct(
        public string $slotId,
        public string $date,
        public string $activityId,
    ) {
    }
}
