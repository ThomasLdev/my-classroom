<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Infrastructure;

use App\Teaching\Infrastructure\Http\CalendarPresenter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CalendarPresenterTest extends TestCase
{
    private CalendarPresenter $presenter;

    protected function setUp(): void
    {
        $this->presenter = new CalendarPresenter();
    }

    public function testHeaderUsesLocalisedFrenchLabels(): void
    {
        // 2026-06-08 is a Monday.
        $header = $this->presenter->header(
            new DateTimeImmutable('2026-06-08'),
            new DateTimeImmutable('2026-06-08'),
        );

        self::assertSame('Lundi', $header['dow']);
        self::assertSame(8, $header['num']);
        self::assertSame('juin', $header['month']);
        self::assertSame(2026, $header['year']);
        self::assertTrue($header['isToday']);
    }

    public function testHeaderIsTodayIsFalseOnAnotherDay(): void
    {
        $header = $this->presenter->header(
            new DateTimeImmutable('2026-06-08'),
            new DateTimeImmutable('2026-06-09'),
        );

        self::assertFalse($header['isToday']);
    }

    public function testWeekAnchorsOnMondayWithLocalisedShortDays(): void
    {
        // Selected a Wednesday, "today" a Friday of the same week.
        $week = $this->presenter->week(
            new DateTimeImmutable('2026-06-10'),
            new DateTimeImmutable('2026-06-12'),
            [],
        );

        self::assertCount(7, $week);
        self::assertSame(
            ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            array_column($week, 'dow'),
        );
        self::assertSame('2026-06-08', $week[0]['date']);
        self::assertSame('2026-06-14', $week[6]['date']);

        self::assertTrue($week[2]['isSelected']); // Wednesday
        self::assertTrue($week[4]['isToday']);     // Friday
        self::assertFalse($week[0]['isWeekend']);
        self::assertTrue($week[5]['isWeekend']);   // Saturday
        self::assertTrue($week[6]['isWeekend']);   // Sunday
    }

    public function testWeekMergesDotsByDate(): void
    {
        $week = $this->presenter->week(
            new DateTimeImmutable('2026-06-08'),
            new DateTimeImmutable('2026-06-08'),
            [
                '2026-06-08' => [
                    'count' => 2,
                    'hasEvent' => false,
                ],
                '2026-06-09' => [
                    'count' => 0,
                    'hasEvent' => true,
                ],
            ],
        );

        self::assertSame(2, $week[0]['dots']);
        self::assertFalse($week[0]['hasEvent']);

        self::assertSame(0, $week[1]['dots']);
        self::assertTrue($week[1]['hasEvent']);

        // A day with no entry defaults to no dots / no event.
        self::assertSame(0, $week[3]['dots']);
        self::assertFalse($week[3]['hasEvent']);
    }
}
