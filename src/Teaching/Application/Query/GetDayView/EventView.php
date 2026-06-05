<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetDayView;

final readonly class EventView
{
    public function __construct(
        public string $id,
        public string $title,
        public string $type,
        public string $startsAt,
        public ?string $endsAt,
        public bool $allDay,
    ) {
    }
}
