<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use App\Teaching\Domain\Model\Session\Activity;
use App\Teaching\Domain\Port\OccurrenceProvider;
use App\Teaching\Domain\Repository\SessionRepository;

final readonly class GetSessionDetailHandler
{
    public function __construct(
        private SessionRepository $sessions,
        private OccurrenceProvider $occurrences,
    ) {
    }

    public function __invoke(GetSessionDetail $query): SessionDetailView
    {
        $slotId = SlotId::fromString($query->slotId);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $query->date)
            ?: throw new \InvalidArgumentException(sprintf('Invalid date "%s".', $query->date));

        $occurrence = $this->occurrences->resolve($slotId, $date)
            ?? throw SlotNotScheduled::for($query->slotId, $query->date);

        $session = $this->sessions->ofOccurrence($slotId, $date);

        $activities = [];
        if ($session !== null) {
            $activities = array_map(
                static fn (Activity $a): ActivityView => new ActivityView(
                    id: (string) $a->id,
                    title: $a->title,
                    done: !$a->isPlanned(),
                    carriedOver: $a->carriedOverFrom !== null,
                    position: $a->position,
                ),
                $session->activities,
            );
        }

        return new SessionDetailView(
            slotId: (string) $occurrence->slotId,
            date: $date->format('Y-m-d'),
            classroomName: $occurrence->classroomName,
            subject: $occurrence->subject,
            room: $occurrence->room,
            start: $occurrence->timeRange->startLabel(),
            end: $occurrence->timeRange->endLabel(),
            materialized: $session !== null,
            activities: $activities,
        );
    }
}
