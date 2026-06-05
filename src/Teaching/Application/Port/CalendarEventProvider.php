<?php

declare(strict_types=1);

namespace App\Teaching\Application\Port;

use App\Teaching\Application\Query\GetDayView\EventView;

/**
 * Read-side port: special events (meetings, deadlines) shown on the calendar.
 * Lives in Application because it serves a query, not a domain invariant.
 */
interface CalendarEventProvider
{
    /**
     * @return list<EventView>
     */
    public function forDay(\DateTimeImmutable $date): array;
}
