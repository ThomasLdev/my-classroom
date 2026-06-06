<?php

declare(strict_types=1);

namespace App\Teaching\Application\Port;

use App\Teaching\Application\Query\GetDayView\EventView;
use DateTimeImmutable;

interface CalendarEventProvider
{
    /**
     * @return list<EventView>
     */
    public function forDay(DateTimeImmutable $date): array;
}
