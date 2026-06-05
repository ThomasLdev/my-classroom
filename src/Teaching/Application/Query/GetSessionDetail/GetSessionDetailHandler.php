<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use App\Teaching\Domain\Model\Session\Activity;
use App\Teaching\Domain\Model\Session\AttachedDocument;
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
        $documents = [];
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
            $documents = array_map(
                static fn (AttachedDocument $d): DocumentView => new DocumentView(
                    id: (string) $d->id,
                    name: $d->name,
                    displayName: self::truncateName($d->name),
                    sizeLabel: self::prettySize($d->size),
                    contentType: $d->contentType,
                ),
                $session->documents,
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
            note: $session?->note,
            documents: $documents,
        );
    }

    private static function truncateName(string $name, int $max = 34): string
    {
        if (mb_strlen($name) <= $max) {
            return $name;
        }

        $dot = mb_strrpos($name, '.');
        if ($dot !== false) {
            $extension = mb_substr($name, $dot + 1);
            $stemLength = $max - mb_strlen($extension) - 2;
            if ($stemLength > 4) {
                return mb_substr($name, 0, $stemLength).'….'.$extension;
            }
        }

        return mb_substr($name, 0, $max - 1).'…';
    }

    private static function prettySize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' o';
        }

        if ($bytes < 1_048_576) {
            return round($bytes / 1024).' Ko';
        }

        return number_format($bytes / 1_048_576, 1, ',', ' ').' Mo';
    }
}
