<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Http\Component;

use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSession;
use App\Teaching\Application\Command\MarkActivityDone\MarkActivityDone;
use App\Teaching\Application\Command\MarkActivityNotDone\MarkActivityNotDone;
use App\Teaching\Application\Query\GetSessionDetail\GetSessionDetail;
use App\Teaching\Application\Query\GetSessionDetail\SessionDetailView;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;

#[AsLiveComponent]
final class SessionActivities
{
    use DefaultActionTrait;
    use HandleTrait;

    #[LiveProp]
    public string $slotId = '';

    #[LiveProp]
    public string $date = '';

    #[LiveProp(writable: true)]
    public string $newTitle = '';

    private readonly MessageBusInterface $commandBus;

    public function __construct(
        #[Autowire(service: 'query.bus')] MessageBusInterface $queryBus,
        #[Autowire(service: 'command.bus')] MessageBusInterface $commandBus,
    ) {
        $this->messageBus = $queryBus;
        $this->commandBus = $commandBus;
    }

    public function getDetail(): SessionDetailView
    {
        return $this->handle(new GetSessionDetail($this->slotId, $this->date));
    }

    #[LiveAction]
    public function addActivity(LiveResponder $live): void
    {
        $title = trim($this->newTitle);
        if ($title === '') {
            return;
        }

        $this->commandBus->dispatch(new AddActivityToSession($this->slotId, $this->date, $title));
        $this->newTitle = '';
        $live->dispatchBrowserEvent('session:changed');
    }

    #[LiveAction]
    public function markDone(#[LiveArg] string $activityId, LiveResponder $live): void
    {
        $this->commandBus->dispatch(new MarkActivityDone($this->slotId, $this->date, $activityId));
        $live->dispatchBrowserEvent('session:changed');
    }

    #[LiveAction]
    public function markNotDone(#[LiveArg] string $activityId, LiveResponder $live): void
    {
        $this->commandBus->dispatch(new MarkActivityNotDone($this->slotId, $this->date, $activityId));
        $live->dispatchBrowserEvent('session:changed');
    }
}
