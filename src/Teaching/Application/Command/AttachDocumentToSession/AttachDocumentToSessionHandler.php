<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\AttachDocumentToSession;

use App\Shared\Domain\Identifier\IdGenerator;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Port\OccurrenceProvider;
use App\Teaching\Application\Port\DocumentStorage;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use App\Teaching\Domain\Model\Session\DocumentId;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;
use App\Teaching\Domain\Repository\SessionRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AttachDocumentToSessionHandler
{
    public function __construct(
        private SessionRepository $sessions,
        private OccurrenceProvider $occurrences,
        private DocumentStorage $storage,
        private IdGenerator $ids,
    ) {
    }

    public function __invoke(AttachDocumentToSession $command): void
    {
        $slotId = SlotId::fromString($command->slotId);
        $date = $this->parseDate($command->date);

        $session = $this->sessions->ofOccurrence($slotId, $date);

        if (! $session instanceof Session) {
            $occurrence = $this->occurrences->resolve($slotId, $date)
                ?? throw SlotNotScheduled::for($command->slotId, $command->date);

            $session = Session::materialize(SessionId::fromString($this->ids->next()), $occurrence);
        }

        $documentId = $this->ids->next();
        $this->storage->store($documentId, $command->sourcePath);

        $session->attachDocument(
            DocumentId::fromString($documentId),
            $command->name,
            $command->size,
            $command->contentType,
        );

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
