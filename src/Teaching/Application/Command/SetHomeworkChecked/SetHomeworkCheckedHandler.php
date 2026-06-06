<?php

declare(strict_types=1);

namespace App\Teaching\Application\Command\SetHomeworkChecked;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Repository\SessionRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class SetHomeworkCheckedHandler
{
    public function __construct(
        private SessionRepository $sessions,
    ) {
    }

    public function __invoke(SetHomeworkChecked $command): void
    {
        $session = $this->sessions->ofOccurrence(
            SlotId::fromString($command->slotId),
            $this->parseDate($command->date),
        );

        if (! $session instanceof Session) {
            return;
        }

        $session->setHomeworkChecked($command->checked);

        $this->sessions->save($session);
    }

    private function parseDate(string $date): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false) {
            throw new InvalidArgumentException(sprintf('Invalid date "%s", expected Y-m-d.', $date));
        }

        return $parsed;
    }
}
