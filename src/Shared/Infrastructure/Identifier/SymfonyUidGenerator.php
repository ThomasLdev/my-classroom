<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Identifier;

use App\Shared\Domain\Identifier\IdGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

#[AsAlias(IdGenerator::class)]
final class SymfonyUidGenerator implements IdGenerator
{
    public function next(): string
    {
        return (string) Uuid::v7();
    }
}
