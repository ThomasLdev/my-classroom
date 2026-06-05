<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\AddActivityToSession;

/**
 * Command (immutable message) dispatched on the command bus.
 * Carries only primitives so any driving adapter (HTTP form, console, test)
 * can build it without touching the domain.
 */
final readonly class AddActivityToSession
{
    public function __construct(
        public string $slotId,
        public string $date,
        public string $title,
    ) {
    }
}
