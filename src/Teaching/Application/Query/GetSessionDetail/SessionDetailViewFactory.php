<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

use App\Shared\Domain\Occurrence;
use App\Teaching\Domain\Model\Session\Session;

final readonly class SessionDetailViewFactory
{
    public function __construct(
        private ActivityViewFactory $activityViewFactory,
        private DocumentViewFactory $documentViewFactory,
    ) {
    }

    public function create(Occurrence $occurrence, ?Session $session, ?Session $previousHomework = null): SessionDetailView
    {
        $activities = $session !== null
            ? array_map($this->activityViewFactory->fromActivity(...), $session->activities)
            : [];

        $documents = $session !== null
            ? array_map($this->documentViewFactory->fromDocument(...), $session->documents)
            : [];

        return new SessionDetailView(
            slotId: (string) $occurrence->slotId,
            date: $occurrence->date->format('Y-m-d'),
            classroomName: $occurrence->classroomName,
            subject: $occurrence->subject,
            room: $occurrence->room,
            start: $occurrence->timeRange->startLabel(),
            end: $occurrence->timeRange->endLabel(),
            materialized: $session !== null,
            activities: array_values($activities),
            note: $session?->note,
            homework: $session?->homework,
            previousHomework: self::previousHomework($previousHomework),
            documents: array_values($documents),
        );
    }

    private static function previousHomework(?Session $session): ?PreviousHomeworkView
    {
        if ($session === null || $session->homework === null || $session->slotId === null) {
            return null;
        }

        return new PreviousHomeworkView(
            slotId: (string) $session->slotId,
            date: $session->date->format('Y-m-d'),
            dateLabel: self::frenchDayMonth($session->date),
            text: $session->homework,
        );
    }

    private static function frenchDayMonth(\DateTimeImmutable $date): string
    {
        $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE);
        $formatter->setPattern('d MMMM');

        return (string) $formatter->format($date);
    }
}
