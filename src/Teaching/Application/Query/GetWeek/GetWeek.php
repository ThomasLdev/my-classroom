<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetWeek;

final readonly class GetWeek
{
    public function __construct(
        public string $date,
    ) {
    }
}
