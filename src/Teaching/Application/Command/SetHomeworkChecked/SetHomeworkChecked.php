<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\SetHomeworkChecked;

final readonly class SetHomeworkChecked
{
    public function __construct(
        public string $slotId,
        public string $date,
        public bool $checked,
    ) {
    }
}
