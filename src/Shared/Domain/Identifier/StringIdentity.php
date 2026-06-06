<?php

declare(strict_types=1);

namespace App\Shared\Domain\Identifier;

use InvalidArgumentException;

trait StringIdentity
{
    final private function __construct(
        public readonly string $value
    ) {
        if ($value === '') {
            throw new InvalidArgumentException('Identifier cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public function equals(self $other): bool
    {
        // The parameter type already guarantees the same final identifier class,
        // so only the underlying value needs comparing.
        return $this->value === $other->value;
    }
}
