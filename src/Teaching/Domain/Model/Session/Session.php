<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Model\Session;

use App\Shared\Domain\Clock;
use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Occurrence;
use App\Shared\Domain\TimeRange;
use App\Teaching\Domain\Exception\ActivityNotFound;

/**
 * Aggregate root for the daily teaching life. A session only exists in the
 * database once it carries something (an activity, a note, a document) or
 * deviates from the timetable (cancelled). Until then it is a virtual
 * occurrence computed from the timetable — never persisted.
 *
 * @phpstan-import-type ActivityStateArray from Activity
 *
 * @phpstan-type SessionStateArray array{
 *     id: string,
 *     classroomId: string,
 *     slotId: string|null,
 *     date: string,
 *     startMinute: int,
 *     endMinute: int,
 *     closedAt: string|null,
 *     cancelled: bool,
 *     activities: list<ActivityStateArray>,
 * }
 */
final class Session
{
    /** @var list<Activity> */
    public private(set) array $activities = [];

    public private(set) ?\DateTimeImmutable $closedAt = null;

    public private(set) bool $cancelled = false;

    private function __construct(
        public readonly SessionId $id,
        public readonly ClassroomId $classroomId,
        public readonly ?SlotId $slotId,
        public readonly \DateTimeImmutable $date,
        public readonly TimeRange $timeRange,
    ) {
    }

    /**
     * Materialise a session from a (previously virtual) timetable occurrence.
     */
    public static function materialize(SessionId $id, Occurrence $occurrence): self
    {
        return new self(
            $id,
            $occurrence->classroomId,
            $occurrence->slotId,
            $occurrence->date,
            $occurrence->timeRange,
        );
    }

    /**
     * Rehydrate the aggregate from its persisted state (Memento pattern).
     * Deliberately bypasses creation invariants: the stored state was valid.
     *
     * @param SessionStateArray $state
     */
    public static function fromState(array $state): self
    {
        $session = new self(
            SessionId::fromString($state['id']),
            ClassroomId::fromString($state['classroomId']),
            $state['slotId'] !== null ? SlotId::fromString($state['slotId']) : null,
            new \DateTimeImmutable($state['date']),
            new TimeRange($state['startMinute'], $state['endMinute']),
        );

        $session->activities = array_values(array_map(
            static fn (array $activity): Activity => Activity::fromState($activity),
            $state['activities'],
        ));
        $session->closedAt = $state['closedAt'] !== null ? new \DateTimeImmutable($state['closedAt']) : null;
        $session->cancelled = $state['cancelled'];

        return $session;
    }

    /**
     * Flat, serializable snapshot of the whole aggregate, tailored for
     * persistence (DB-ready primitives, no value objects leaking out).
     *
     * @return SessionStateArray
     */
    public function toState(): array
    {
        return [
            'id' => (string) $this->id,
            'classroomId' => (string) $this->classroomId,
            'slotId' => $this->slotId !== null ? (string) $this->slotId : null,
            'date' => $this->date->format('Y-m-d'),
            'startMinute' => $this->timeRange->startMinute,
            'endMinute' => $this->timeRange->endMinute,
            'closedAt' => $this->closedAt?->format(\DateTimeInterface::ATOM),
            'cancelled' => $this->cancelled,
            'activities' => array_map(
                static fn (Activity $activity): array => $activity->toState(),
                $this->activities,
            ),
        ];
    }

    public function addActivity(ActivityId $id, string $title): Activity
    {
        $activity = Activity::plan($id, $title, $this->nextPosition());
        $this->activities[] = $activity;

        return $activity;
    }

    public function receiveCarriedOver(ActivityId $newId, Activity $source): Activity
    {
        $activity = Activity::carriedFrom($newId, $source, $this->nextPosition());
        $this->activities[] = $activity;

        return $activity;
    }

    public function hasElapsed(Clock $clock): bool
    {
        return $this->endsAt() <= $clock->now();
    }

    public function isClosed(): bool
    {
        return $this->closedAt !== null;
    }

    /**
     * The session's time is over: close it and return the activities that were
     * still planned, so the caller can carry them to the next occurrence.
     * Idempotent — a session already closed yields nothing.
     *
     * @return list<Activity>
     */
    public function close(Clock $clock): array
    {
        if ($this->isClosed()) {
            return [];
        }

        $this->closedAt = $clock->now();

        return array_values(
            array_filter($this->activities, static fn (Activity $a): bool => $a->isPlanned()),
        );
    }

    public function cancel(): void
    {
        $this->cancelled = true;
    }

    public function markActivityDone(ActivityId $id): void
    {
        $this->activityWith($id)->markDone();
    }

    public function markActivityNotDone(ActivityId $id): void
    {
        $this->activityWith($id)->markNotDone();
    }

    public function endsAt(): \DateTimeImmutable
    {
        return $this->timeRange->endsOn($this->date);
    }

    public function activityCount(): int
    {
        return \count($this->activities);
    }

    public function doneCount(): int
    {
        return \count(array_filter($this->activities, static fn (Activity $a): bool => !$a->isPlanned()));
    }

    private function activityWith(ActivityId $id): Activity
    {
        foreach ($this->activities as $activity) {
            if ($activity->id->equals($id)) {
                return $activity;
            }
        }

        throw ActivityNotFound::inSession($this->id, $id);
    }

    private function nextPosition(): int
    {
        if ($this->activities === []) {
            return 1;
        }

        return max(array_map(static fn (Activity $a): int => $a->position, $this->activities)) + 1;
    }
}
