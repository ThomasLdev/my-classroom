<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetDayView;

final readonly class GetDayView
{
    public function __construct(
        public string $date,
    ) {
    }
}
