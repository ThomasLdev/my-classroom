<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Repository;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;

interface SessionRepository
{
    public function ofId(SessionId $id): ?Session;

    public function ofOccurrence(SlotId $slotId, \DateTimeImmutable $date): ?Session;

    public function save(Session $session): void;

    /**
     * Ordered chronologically so carry-over cascades correctly in one run.
     *
     * @return list<Session>
     */
    public function elapsedOpenWithPlannedActivities(\DateTimeImmutable $now): array;
}
