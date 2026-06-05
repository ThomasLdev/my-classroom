<?php

declare(strict_types=1);

namespace App\Teaching\Domain\Repository;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;

/**
 * Driven port for persisting and retrieving Session aggregates.
 */
interface SessionRepository
{
    public function ofId(SessionId $id): ?Session;

    /**
     * Resolve a materialised session by its occurrence identity (slot + date).
     * Returns null when the occurrence is still virtual.
     */
    public function ofOccurrence(SlotId $slotId, \DateTimeImmutable $date): ?Session;

    public function save(Session $session): void;

    /**
     * Sessions whose time slot has elapsed, not yet closed, and still holding
     * planned activities — the work list of the carry-over scheduler.
     * Ordered chronologically so carry-over cascades correctly in one run.
     *
     * @return list<Session>
     */
    public function elapsedOpenWithPlannedActivities(\DateTimeImmutable $now): array;
}
