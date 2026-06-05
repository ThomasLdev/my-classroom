<?php

declare(strict_types=1);

namespace App\Shared\Domain\Identifier;

/**
 * Driven port: produces new identifier values.
 * Implemented in Infrastructure (Symfony Uid) and faked in tests.
 */
interface IdGenerator
{
    public function next(): string;
}
