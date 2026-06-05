<?php

declare(strict_types=1);

namespace App\Scheduling\Domain;

enum DayOfWeek: int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    public static function fromDate(\DateTimeImmutable $date): self
    {
        return self::from((int) $date->format('N'));
    }

    public function matches(\DateTimeImmutable $date): bool
    {
        return (int) $date->format('N') === $this->value;
    }
}
