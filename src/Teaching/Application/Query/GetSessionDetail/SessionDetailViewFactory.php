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

    public function create(Occurrence $occurrence, ?Session $session): SessionDetailView
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
            documents: array_values($documents),
        );
    }
}
