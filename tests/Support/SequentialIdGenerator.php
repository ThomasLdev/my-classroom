<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Domain\Identifier\IdGenerator;

final class SequentialIdGenerator implements IdGenerator
{
    private int $counter = 0;

    public function __construct(
        private readonly string $prefix = 'id'
    ) {
    }

    public function next(): string
    {
        return sprintf('%s-%d', $this->prefix, ++$this->counter);
    }
}
