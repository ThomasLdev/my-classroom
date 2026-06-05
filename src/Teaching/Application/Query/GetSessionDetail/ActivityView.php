<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

final readonly class ActivityView
{
    public function __construct(
        public string $id,
        public string $title,
        public bool $done,
        public bool $carriedOver,
        public int $position,
    ) {
    }
}
