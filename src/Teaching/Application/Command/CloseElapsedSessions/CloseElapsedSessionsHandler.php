<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\CloseElapsedSessions;

use App\Shared\Domain\Clock;
use App\Shared\Domain\Identifier\IdGenerator;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Occurrence;
use App\Teaching\Domain\Model\Session\Activity;
use App\Teaching\Domain\Model\Session\ActivityId;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;
use App\Teaching\Domain\Port\OccurrenceProvider;
use App\Teaching\Domain\Repository\SessionRepository;

// Identity map keyed by occurrence so a later carry target is the same aggregate instance (cascade A -> B -> C in one run).
final readonly class CloseElapsedSessionsHandler
{
    public function __construct(
        private SessionRepository $sessions,
        private OccurrenceProvider $occurrences,
        private IdGenerator $ids,
        private Clock $clock,
    ) {
    }

    public function __invoke(CloseElapsedSessions $command): void
    {
        $now = $this->clock->now();
        $elapsed = $this->sessions->elapsedOpenWithPlannedActivities($now);

        /** @var array<string, Session> $identityMap */
        $identityMap = [];
        foreach ($elapsed as $session) {
            $identityMap[$this->keyOf($session)] = $session;
        }

        foreach ($elapsed as $session) {
            $planned = $session->close($this->clock);

            if ($planned !== []) {
                $this->carryOver($session, $planned, $identityMap);
            }

            $this->sessions->save($session);
        }
    }

    /**
     * @param list<Activity>          $planned
     * @param array<string, Session>  $identityMap
     */
    private function carryOver(Session $source, array $planned, array &$identityMap): void
    {
        $next = $this->occurrences->nextAfter($source->classroomId, $source->endsAt());

        if (! $next instanceof Occurrence) {
            return; // nothing scheduled ahead; planned activities stay on the closed session as history
        }

        $key = sprintf('slot:%s@%s', $next->slotId, $next->date->format('Y-m-d'));

        $target = $identityMap[$key]
            ?? $this->sessions->ofOccurrence($next->slotId, $next->date)
            ?? Session::materialize(SessionId::fromString($this->ids->next()), $next);

        foreach ($planned as $activity) {
            $target->receiveCarriedOver(ActivityId::fromString($this->ids->next()), $activity);
        }

        $identityMap[$key] = $target;
        $this->sessions->save($target);
    }

    private function keyOf(Session $session): string
    {
        $slotId = $session->slotId;

        return $slotId instanceof SlotId
            ? sprintf('slot:%s@%s', $slotId, $session->date->format('Y-m-d'))
            : sprintf('sid:%s', $session->id);
    }
}
