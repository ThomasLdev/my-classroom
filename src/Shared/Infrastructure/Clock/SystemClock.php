<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Clock;

use App\Shared\Domain\Clock;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(Clock::class)]
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
