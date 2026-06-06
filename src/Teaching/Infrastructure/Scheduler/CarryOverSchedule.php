<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Scheduler;

use App\Teaching\Application\Command\CloseElapsedSessions\CloseElapsedSessions;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

// 5 minutes keeps carry-over tight enough for back-to-back lessons.
#[AsSchedule('teaching')]
final class CarryOverSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return new Schedule()->add(
            RecurringMessage::every('5 minutes', new CloseElapsedSessions()),
        );
    }
}
