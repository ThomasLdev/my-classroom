<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetWeek;

use App\Teaching\Application\Port\CalendarEventProvider;
use App\Teaching\Domain\Port\OccurrenceProvider;

final readonly class GetWeekHandler
{
    public function __construct(
        private OccurrenceProvider $occurrences,
        private CalendarEventProvider $events,
    ) {
    }

    public function __invoke(GetWeek $query): WeekView
    {
        $monday = self::parseDate($query->date)->modify('monday this week');

        $days = [];
        for ($i = 0; $i < 7; ++$i) {
            $cursor = $monday->modify(sprintf('+%d days', $i));

            $names = [];
            foreach ($this->occurrences->forDay($cursor) as $occurrence) {
                if (!in_array($occurrence->classroomName, $names, true)) {
                    $names[] = $occurrence->classroomName;
                }
            }

            $days[] = new WeekDayView(
                date: $cursor->format('Y-m-d'),
                classroomNames: $names,
                hasEvent: $this->events->forDay($cursor) !== [],
            );
        }

        return new WeekView($days);
    }

    private static function parseDate(string $date): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false) {
            throw new \InvalidArgumentException(sprintf('Invalid date "%s", expected Y-m-d.', $date));
        }

        return $parsed;
    }
}
