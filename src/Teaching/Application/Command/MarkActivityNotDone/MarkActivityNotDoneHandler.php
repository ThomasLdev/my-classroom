<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\MarkActivityNotDone;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Exception\SessionNotFound;
use App\Teaching\Domain\Model\Session\ActivityId;
use App\Teaching\Domain\Repository\SessionRepository;

final readonly class MarkActivityNotDoneHandler
{
    public function __construct(
        private SessionRepository $sessions,
    ) {
    }

    public function __invoke(MarkActivityNotDone $command): void
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $command->date)
            ?: throw new \InvalidArgumentException(sprintf('Invalid date "%s".', $command->date));

        $session = $this->sessions->ofOccurrence(SlotId::fromString($command->slotId), $date)
            ?? throw SessionNotFound::forOccurrence($command->slotId, $command->date);

        $session->markActivityNotDone(ActivityId::fromString($command->activityId));

        $this->sessions->save($session);
    }
}
