<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * A time-of-day range, independent of any date.
 * Stored as minutes-from-midnight to stay a pure, comparable value object.
 */
final readonly class TimeRange
{
    public function __construct(
        public int $startMinute,
        public int $endMinute,
    ) {
        if ($startMinute < 0 || $endMinute > 24 * 60) {
            throw new \InvalidArgumentException('Time range out of bounds.');
        }
        if ($endMinute <= $startMinute) {
            throw new \InvalidArgumentException('Time range end must be after its start.');
        }
    }

    public static function fromLabels(string $start, string $end): self
    {
        return new self(self::toMinutes($start), self::toMinutes($end));
    }

    public function startLabel(): string
    {
        return self::label($this->startMinute);
    }

    public function endLabel(): string
    {
        return self::label($this->endMinute);
    }

    public function startsOn(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->setTime(intdiv($this->startMinute, 60), $this->startMinute % 60);
    }

    public function endsOn(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->setTime(intdiv($this->endMinute, 60), $this->endMinute % 60);
    }

    private static function toMinutes(string $hhmm): int
    {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hhmm, $m)) {
            throw new \InvalidArgumentException(sprintf('Invalid time label "%s", expected HH:MM.', $hhmm));
        }

        return ((int) $m[1]) * 60 + (int) $m[2];
    }

    private static function label(int $minute): string
    {
        return sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
    }
}
