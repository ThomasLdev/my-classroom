<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetDayView;

final readonly class DayView
{
    /**
     * @param list<SessionView> $sessions
     * @param list<EventView>   $events
     */
    public function __construct(
        public string $date,
        public array $sessions,
        public array $events,
    ) {
    }
}
