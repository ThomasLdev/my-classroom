<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

final readonly class GetSessionDetail
{
    public function __construct(
        public string $slotId,
        public string $date,
    ) {
    }
}
