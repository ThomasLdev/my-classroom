<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\RemoveDocumentFromSession;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Application\Port\DocumentStorage;
use App\Teaching\Domain\Model\Session\DocumentId;
use App\Teaching\Domain\Repository\SessionRepository;

final readonly class RemoveDocumentFromSessionHandler
{
    public function __construct(
        private SessionRepository $sessions,
        private DocumentStorage $storage,
    ) {
    }

    public function __invoke(RemoveDocumentFromSession $command): void
    {
        $session = $this->sessions->ofOccurrence(
            SlotId::fromString($command->slotId),
            self::parseDate($command->date),
        );

        if ($session === null) {
            return;
        }

        $session->removeDocument(DocumentId::fromString($command->documentId));
        $this->storage->delete($command->documentId);

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
