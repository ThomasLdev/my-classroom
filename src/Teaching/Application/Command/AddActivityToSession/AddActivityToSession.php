<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\AddActivityToSession;

final readonly class AddActivityToSession
{
    public function __construct(
        public string $slotId,
        public string $date,
        public string $title,
    ) {
    }
}
