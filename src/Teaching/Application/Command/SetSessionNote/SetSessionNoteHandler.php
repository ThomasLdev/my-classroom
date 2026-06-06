<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\SetSessionNote;

use App\Shared\Domain\Identifier\IdGenerator;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Port\OccurrenceProvider;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;
use App\Teaching\Domain\Repository\SessionRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class SetSessionNoteHandler
{
    public function __construct(
        private SessionRepository $sessions,
        private OccurrenceProvider $occurrences,
        private IdGenerator $ids,
    ) {
    }

    public function __invoke(SetSessionNote $command): void
    {
        $slotId = SlotId::fromString($command->slotId);
        $date = $this->parseDate($command->date);

        $session = $this->sessions->ofOccurrence($slotId, $date);

        // Don't materialise a session just to store an empty note.
        if (! $session instanceof Session && trim($command->note) === '') {
            return;
        }

        if (! $session instanceof Session) {
            $occurrence = $this->occurrences->resolve($slotId, $date)
                ?? throw SlotNotScheduled::for($command->slotId, $command->date);

            $session = Session::materialize(SessionId::fromString($this->ids->next()), $occurrence);
        }

        $session->setNote($command->note);

        $this->sessions->save($session);
    }

    private function parseDate(string $date): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false) {
            throw new InvalidArgumentException(sprintf('Invalid date "%s", expected Y-m-d.', $date));
        }

        return $parsed;
    }
}
