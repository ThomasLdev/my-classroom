<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\AddActivityToSession;

use App\Shared\Domain\Identifier\IdGenerator;
use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use App\Teaching\Domain\Model\Session\ActivityId;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;
use App\Teaching\Domain\Port\OccurrenceProvider;
use App\Teaching\Domain\Repository\SessionRepository;

/**
 * Lazily materialises the session (find-or-create on the occurrence identity)
 * then appends an activity. No flush here: the transaction boundary commits.
 */
final readonly class AddActivityToSessionHandler
{
    public function __construct(
        private SessionRepository $sessions,
        private OccurrenceProvider $occurrences,
        private IdGenerator $ids,
    ) {
    }

    public function __invoke(AddActivityToSession $command): void
    {
        $slotId = SlotId::fromString($command->slotId);
        $date = self::parseDate($command->date);

        $session = $this->sessions->ofOccurrence($slotId, $date);

        if ($session === null) {
            $occurrence = $this->occurrences->resolve($slotId, $date)
                ?? throw SlotNotScheduled::for($command->slotId, $command->date);

            $session = Session::materialize(SessionId::fromString($this->ids->next()), $occurrence);
        }

        $session->addActivity(ActivityId::fromString($this->ids->next()), $command->title);

        $this->sessions->save($session);
    }

    private static function parseDate(string $date): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false) {
            throw new \InvalidArgumentException(sprintf('Invalid date "%s", expected Y-m-d.', $date));
        }

        return $parsed;
    }
}
