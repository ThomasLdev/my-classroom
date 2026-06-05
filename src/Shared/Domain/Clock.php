<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Driven port: the domain never reads the wall clock directly.
 * This keeps "today" / "now" deterministic and testable.
 */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
