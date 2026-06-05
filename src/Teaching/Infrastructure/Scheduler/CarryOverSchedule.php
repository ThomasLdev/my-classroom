<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Scheduler;

use App\Teaching\Application\Command\CloseElapsedSessions\CloseElapsedSessions;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Time decides: every 5 minutes we close elapsed sessions and carry their
 * unfinished activities forward. 5 minutes keeps the carry-over tight enough
 * for back-to-back lessons; raise it later if telemetry shows idle runs.
 *
 * Consume with: bin/console messenger:consume scheduler_teaching
 */
#[AsSchedule('teaching')]
final class CarryOverSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(
            RecurringMessage::every('5 minutes', new CloseElapsedSessions()),
        );
    }
}
