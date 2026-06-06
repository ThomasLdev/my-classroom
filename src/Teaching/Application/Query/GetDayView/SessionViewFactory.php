<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetDayView;

use App\Shared\Domain\Occurrence;
use App\Teaching\Domain\Model\Session\Session;

final class SessionViewFactory
{
    public function create(Occurrence $occurrence, ?Session $session): SessionView
    {
        return new SessionView(
            slotId: (string) $occurrence->slotId,
            sessionId: $session instanceof Session ? (string) $session->id : null,
            classroomName: $occurrence->classroomName,
            subject: $occurrence->subject,
            room: $occurrence->room,
            start: $occurrence->timeRange->startLabel(),
            end: $occurrence->timeRange->endLabel(),
            activityCount: $session?->activityCount() ?? 0,
            doneCount: $session?->doneCount() ?? 0,
            documentCount: $session?->documentCount() ?? 0,
            hasNote: $session?->note !== null,
            cancelled: $session instanceof Session && $session->cancelled,
            materialized: $session instanceof Session,
        );
    }
}
