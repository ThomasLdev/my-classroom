<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\SetSessionHomework;

final readonly class SetSessionHomework
{
    public function __construct(
        public string $slotId,
        public string $date,
        public string $homework,
    ) {
    }
}
