<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\RemoveDocumentFromSession;

final readonly class RemoveDocumentFromSession
{
    public function __construct(
        public string $slotId,
        public string $date,
        public string $documentId,
    ) {
    }
}
