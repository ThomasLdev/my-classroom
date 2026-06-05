<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\AttachDocumentToSession;

final readonly class AttachDocumentToSession
{
    public function __construct(
        public string $slotId,
        public string $date,
        public string $name,
        public int $size,
        public string $contentType,
        public string $sourcePath,
    ) {
    }
}
