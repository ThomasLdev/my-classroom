<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Teaching\Application\Port\CalendarEventProvider;
use App\Teaching\Application\Query\GetDayView\EventView;
use DateTimeImmutable;

final readonly class InMemoryCalendarEventProvider implements CalendarEventProvider
{
    /**
     * @param list<EventView> $events
     */
    public function __construct(
        private array $events = []
    ) {
    }

    public function forDay(DateTimeImmutable $date): array
    {
        return array_values(array_filter(
            $this->events,
            static fn (EventView $event): bool => str_starts_with($event->startsAt, $date->format('Y-m-d')),
        ));
    }
}
