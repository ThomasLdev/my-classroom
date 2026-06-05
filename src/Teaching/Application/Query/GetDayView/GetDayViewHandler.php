<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetDayView;

use App\Teaching\Application\Port\CalendarEventProvider;
use App\Teaching\Domain\Port\OccurrenceProvider;
use App\Teaching\Domain\Repository\SessionRepository;

final readonly class GetDayViewHandler
{
    public function __construct(
        private SessionRepository $sessions,
        private OccurrenceProvider $occurrences,
        private CalendarEventProvider $events,
    ) {
    }

    public function __invoke(GetDayView $query): DayView
    {
        $date = self::parseDate($query->date);

        $sessionViews = [];
        foreach ($this->occurrences->forDay($date) as $occurrence) {
            $session = $this->sessions->ofOccurrence($occurrence->slotId, $occurrence->date);

            $sessionViews[] = new SessionView(
                slotId: (string) $occurrence->slotId,
                sessionId: $session !== null ? (string) $session->id : null,
                classroomName: $occurrence->classroomName,
                subject: $occurrence->subject,
                room: $occurrence->room,
                start: $occurrence->timeRange->startLabel(),
                end: $occurrence->timeRange->endLabel(),
                activityCount: $session?->activityCount() ?? 0,
                doneCount: $session?->doneCount() ?? 0,
                cancelled: $session?->cancelled ?? false,
                materialized: $session !== null,
            );
        }

        return new DayView(
            date: $date->format('Y-m-d'),
            sessions: $sessionViews,
            events: $this->events->forDay($date),
        );
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
