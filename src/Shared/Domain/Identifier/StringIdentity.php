<?php

declare(strict_types=1);

namespace App\Shared\Domain\Identifier;

/**
 * Shared behaviour for UUID-backed identifiers, kept framework-free.
 * Generation is delegated to {@see IdGenerator} so the domain never
 * depends on a concrete UUID library.
 */
trait StringIdentity
{
    final private function __construct(public readonly string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Identifier cannot be empty.');
        }
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public function equals(self $other): bool
    {
        return $this::class === $other::class && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
