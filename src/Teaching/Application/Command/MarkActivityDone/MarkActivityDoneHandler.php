<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\MarkActivityDone;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Exception\SessionNotFound;
use App\Teaching\Domain\Model\Session\ActivityId;
use App\Teaching\Domain\Repository\SessionRepository;

final readonly class MarkActivityDoneHandler
{
    public function __construct(
        private SessionRepository $sessions,
    ) {
    }

    public function __invoke(MarkActivityDone $command): void
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $command->date)
            ?: throw new \InvalidArgumentException(sprintf('Invalid date "%s".', $command->date));

        $session = $this->sessions->ofOccurrence(SlotId::fromString($command->slotId), $date)
            ?? throw SessionNotFound::forOccurrence($command->slotId, $command->date);

        $session->markActivityDone(ActivityId::fromString($command->activityId));

        $this->sessions->save($session);
    }
}
