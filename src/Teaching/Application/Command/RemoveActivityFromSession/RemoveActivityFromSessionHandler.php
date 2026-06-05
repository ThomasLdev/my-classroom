<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\RemoveActivityFromSession;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Model\Session\ActivityId;
use App\Teaching\Domain\Repository\SessionRepository;

final readonly class RemoveActivityFromSessionHandler
{
    public function __construct(
        private SessionRepository $sessions,
    ) {
    }

    public function __invoke(RemoveActivityFromSession $command): void
    {
        $session = $this->sessions->ofOccurrence(
            SlotId::fromString($command->slotId),
            self::parseDate($command->date),
        );

        if ($session === null) {
            return;
        }

        $session->removeActivity(ActivityId::fromString($command->activityId));

        $this->sessions->save($session);
    }

    private static function parseDate(string $date): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false) {
            throw new \InvalidArgumentException(sprintf('Invalid date "%s", expected Y-m-d.', $date));
        }

        return $parsed;
    }
}
