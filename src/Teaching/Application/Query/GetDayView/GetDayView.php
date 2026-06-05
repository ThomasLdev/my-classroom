<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetDayView;

/**
 * Query for the calendar's core view: a single day, ready for the swipe.
 */
final readonly class GetDayView
{
    public function __construct(
        public string $date,
    ) {
    }
}
