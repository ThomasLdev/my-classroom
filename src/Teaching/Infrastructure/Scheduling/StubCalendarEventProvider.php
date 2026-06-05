<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Scheduling;

use App\Teaching\Application\Port\CalendarEventProvider;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Placeholder adapter for calendar events (meetings, deadlines).
 *
 * @todo Implement against the Scheduling context (CalendarEvent aggregate).
 */
#[AsAlias(CalendarEventProvider::class)]
final class StubCalendarEventProvider implements CalendarEventProvider
{
    public function forDay(\DateTimeImmutable $date): array
    {
        return [];
    }
}
