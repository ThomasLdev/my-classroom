<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Repository;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;

interface SessionRepository
{
    public function ofId(SessionId $id): ?Session;

    public function ofOccurrence(SlotId $slotId, \DateTimeImmutable $date): ?Session;

    /**
     * Most recent session of a classroom, before $date, whose homework is set but
     * not yet verified — used to remind the teacher in the following session.
     */
    public function mostRecentUncheckedHomework(ClassroomId $classroomId, \DateTimeImmutable $before): ?Session;

    public function save(Session $session): void;

    /**
     * Ordered chronologically so carry-over cascades correctly in one run.
     *
     * @return list<Session>
     */
    public function elapsedOpenWithPlannedActivities(\DateTimeImmutable $now): array;
}
