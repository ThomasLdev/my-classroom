<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

final readonly class DocumentView
{
    public function __construct(
        public string $id,
        public string $name,
        public string $displayName,
        public string $sizeLabel,
        public string $contentType,
    ) {
    }
}
