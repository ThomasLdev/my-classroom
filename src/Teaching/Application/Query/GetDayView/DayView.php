<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetDayView;

/**
 * UX-oriented read model for one calendar day. Shaped to be consumed directly
 * by Twig/Turbo/LiveComponent without any further query.
 */
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
