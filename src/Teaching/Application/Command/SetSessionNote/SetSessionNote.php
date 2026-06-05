<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\SetSessionNote;

final readonly class SetSessionNote
{
    public function __construct(
        public string $slotId,
        public string $date,
        public string $note,
    ) {
    }
}
