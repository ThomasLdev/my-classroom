<?php

declare(strict_types=1);

namespace App\Teaching\Application\Query\GetSessionDetail;

use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use App\Teaching\Domain\Port\OccurrenceProvider;
use App\Teaching\Domain\Repository\SessionRepository;

final readonly class GetSessionDetailHandler
{
    public function __construct(
        private SessionRepository $sessions,
        private OccurrenceProvider $occurrences,
        private SessionDetailViewFactory $viewFactory,
    ) {
    }

    public function __invoke(GetSessionDetail $query): SessionDetailView
    {
        $slotId = SlotId::fromString($query->slotId);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $query->date)
            ?: throw new \InvalidArgumentException(sprintf('Invalid date "%s".', $query->date));

        $occurrence = $this->occurrences->resolve($slotId, $date)
            ?? throw SlotNotScheduled::for($query->slotId, $query->date);

        return $this->viewFactory->create($occurrence, $this->sessions->ofOccurrence($slotId, $date));
    }
}
