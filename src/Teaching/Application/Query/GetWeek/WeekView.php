<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetWeek;

final readonly class WeekView
{
    /**
     * @param list<WeekDayView> $days Monday-first, seven entries
     */
    public function __construct(
        public array $days,
    ) {
    }
}
