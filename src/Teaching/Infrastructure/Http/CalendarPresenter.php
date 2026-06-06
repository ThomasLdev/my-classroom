<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Http;

/**
 * Builds the calendar page chrome (header + week strip) with locale-aware labels,
 * so the controller carries no hardcoded French day/month names.
 */
final class CalendarPresenter
{
    private const string LOCALE = 'fr_FR';

    /**
     * @return array{dow: string, num: int, month: string, year: int, isToday: bool}
     */
    public function header(\DateTimeImmutable $selected, \DateTimeImmutable $today): array
    {
        return [
            'dow' => $this->weekday($selected),
            'num' => (int) $selected->format('j'),
            'month' => $this->month($selected),
            'year' => (int) $selected->format('Y'),
            'isToday' => self::sameDay($selected, $today),
        ];
    }

    /**
     * @param array<string, array{count: int, hasEvent: bool}> $dotsByDate keyed by Y-m-d
     *
     * @return list<array{date: string, dow: string, num: int, isSelected: bool, isToday: bool, isWeekend: bool, dots: int, hasEvent: bool}>
     */
    public function week(\DateTimeImmutable $selected, \DateTimeImmutable $today, array $dotsByDate): array
    {
        $monday = $selected->modify('monday this week');

        $week = [];
        foreach (new \DatePeriod($monday, new \DateInterval('P1D'), $monday->modify('+7 days')) as $cursor) {
            $key = $cursor->format('Y-m-d');
            $week[] = [
                'date' => $key,
                'dow' => $this->shortWeekday($cursor),
                'num' => (int) $cursor->format('j'),
                'isSelected' => self::sameDay($cursor, $selected),
                'isToday' => self::sameDay($cursor, $today),
                'isWeekend' => (int) $cursor->format('N') >= 6,
                'dots' => $dotsByDate[$key]['count'] ?? 0,
                'hasEvent' => $dotsByDate[$key]['hasEvent'] ?? false,
            ];
        }

        return $week;
    }

    private function weekday(\DateTimeImmutable $date): string
    {
        return ucfirst($this->format($date, 'EEEE'));
    }

    private function shortWeekday(\DateTimeImmutable $date): string
    {
        // fr_FR short days come as "lun." — drop the dot and capitalise: "Lun".
        return ucfirst(rtrim($this->format($date, 'EEE'), '.'));
    }

    private function month(\DateTimeImmutable $date): string
    {
        return $this->format($date, 'MMMM');
    }

    private function format(\DateTimeImmutable $date, string $pattern): string
    {
        $formatter = new \IntlDateFormatter(self::LOCALE, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE);
        $formatter->setPattern($pattern);

        return (string) $formatter->format($date);
    }

    private static function sameDay(\DateTimeImmutable $a, \DateTimeImmutable $b): bool
    {
        return $a->format('Y-m-d') === $b->format('Y-m-d');
    }
}
