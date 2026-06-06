<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;
use App\Teaching\Domain\Repository\SessionRepository;

final class InMemorySessionRepository implements SessionRepository
{
    /** @var array<string, Session> */
    private array $byId = [];

    public function ofId(SessionId $id): ?Session
    {
        return $this->byId[(string) $id] ?? null;
    }

    public function ofOccurrence(SlotId $slotId, \DateTimeImmutable $date): ?Session
    {
        foreach ($this->byId as $session) {
            if ($session->slotId !== null
                && $session->slotId->equals($slotId)
                && $session->date->format('Y-m-d') === $date->format('Y-m-d')
            ) {
                return $session;
            }
        }

        return null;
    }

    public function mostRecentUncheckedHomework(ClassroomId $classroomId, \DateTimeImmutable $before): ?Session
    {
        $matches = array_filter($this->byId, static fn (Session $s): bool => $s->classroomId->equals($classroomId)
            && $s->homework !== null
            && !$s->homeworkChecked
            && $s->date < $before->setTime(0, 0));

        usort($matches, static fn (Session $a, Session $b): int => $b->date <=> $a->date);

        return $matches[0] ?? null;
    }

    public function save(Session $session): void
    {
        $this->byId[(string) $session->id] = $session;
    }

    public function elapsedOpenWithPlannedActivities(\DateTimeImmutable $now): array
    {
        $list = array_filter($this->byId, static function (Session $session) use ($now): bool {
            if ($session->isClosed() || $session->endsAt() > $now) {
                return false;
            }

            foreach ($session->activities as $activity) {
                if ($activity->isPlanned()) {
                    return true;
                }
            }

            return false;
        });

        usort($list, static fn (Session $a, Session $b): int => $a->endsAt() <=> $b->endsAt());

        return array_values($list);
    }

    /** @return list<Session> */
    public function all(): array
    {
        return array_values($this->byId);
    }
}
