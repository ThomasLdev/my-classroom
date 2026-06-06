<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetWeek;

use App\Shared\Domain\Port\OccurrenceProvider;
use App\Teaching\Application\Port\CalendarEventProvider;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class GetWeekHandler
{
    public function __construct(
        private OccurrenceProvider $occurrences,
        private CalendarEventProvider $events,
        private WeekDayViewFactory $viewFactory,
    ) {
    }

    public function __invoke(GetWeek $query): WeekView
    {
        $monday = $this->parseDate($query->date)->modify('monday this week');

        $days = [];
        for ($i = 0; $i < 7; ++$i) {
            $cursor = $monday->modify(sprintf('+%d days', $i));
            $days[] = $this->viewFactory->create(
                $cursor,
                $this->occurrences->forDay($cursor),
                $this->events->forDay($cursor) !== [],
            );
        }

        return new WeekView($days);
    }

    private function parseDate(string $date): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false) {
            throw new InvalidArgumentException(sprintf('Invalid date "%s", expected Y-m-d.', $date));
        }

        return $parsed;
    }
}
