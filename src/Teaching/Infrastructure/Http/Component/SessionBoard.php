<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Http\Component;

use App\Teaching\Application\Command\AddActivityToSession\AddActivityToSession;
use App\Teaching\Application\Command\MarkActivityDone\MarkActivityDone;
use App\Teaching\Application\Command\MarkActivityNotDone\MarkActivityNotDone;
use App\Teaching\Application\Command\RemoveActivityFromSession\RemoveActivityFromSession;
use App\Teaching\Application\Command\SetHomeworkChecked\SetHomeworkChecked;
use App\Teaching\Application\Command\SetSessionHomework\SetSessionHomework;
use App\Teaching\Application\Command\SetSessionNote\SetSessionNote;
use App\Teaching\Application\Query\GetSessionDetail\GetSessionDetail;
use App\Teaching\Application\Query\GetSessionDetail\SessionDetailView;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class SessionBoard
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use HandleTrait;

    #[LiveProp]
    public string $slotId = '';

    #[LiveProp]
    public string $date = '';

    #[LiveProp(writable: true)]
    public string $newTitle = '';

    #[LiveProp(writable: true, onUpdated: 'onNoteUpdated')]
    public string $note = '';

    #[LiveProp(writable: true, onUpdated: 'onHomeworkUpdated')]
    public string $homework = '';

    private readonly MessageBusInterface $commandBus;

    private ?SessionDetailView $detail = null;

    public function __construct(
        #[Autowire(service: 'query.bus')] MessageBusInterface $queryBus,
        #[Autowire(service: 'command.bus')] MessageBusInterface $commandBus,
    ) {
        $this->messageBus = $queryBus;
        $this->commandBus = $commandBus;
    }

    public function mount(string $slotId, string $date): void
    {
        $this->slotId = $slotId;
        $this->date = $date;
        $this->note = $this->getDetail()->note ?? '';
        $this->homework = $this->getDetail()->homework ?? '';
    }

    public function getDetail(): SessionDetailView
    {
        return $this->detail ??= $this->handle(new GetSessionDetail($this->slotId, $this->date));
    }

    public function onNoteUpdated(string $previousValue): void
    {
        $this->commandBus->dispatch(new SetSessionNote($this->slotId, $this->date, $this->note));
        $this->detail = null;

        // Refresh the day card only when the note's presence flips (its indicator changes).
        if ((trim($previousValue) === '') !== (trim($this->note) === '')) {
            $this->dispatchBrowserEvent('session:changed');
        }
    }

    public function onHomeworkUpdated(): void
    {
        $this->commandBus->dispatch(new SetSessionHomework($this->slotId, $this->date, $this->homework));
        $this->detail = null;
    }

    #[LiveAction]
    public function verifyHomework(): void
    {
        $previous = $this->getDetail()->previousHomework;
        if ($previous === null) {
            return;
        }

        $this->commandBus->dispatch(new SetHomeworkChecked($previous->slotId, $previous->date, true));
        $this->detail = null;
    }

    #[LiveAction]
    public function addActivity(): void
    {
        $title = trim($this->newTitle);
        if ($title === '') {
            return;
        }

        $this->commandBus->dispatch(new AddActivityToSession($this->slotId, $this->date, $title));
        $this->newTitle = '';
        $this->detail = null;
        $this->dispatchBrowserEvent('session:changed');
    }

    #[LiveAction]
    public function markDone(#[LiveArg] string $activityId): void
    {
        $this->commandBus->dispatch(new MarkActivityDone($this->slotId, $this->date, $activityId));
        $this->detail = null;
        $this->dispatchBrowserEvent('session:changed');
    }

    #[LiveAction]
    public function markNotDone(#[LiveArg] string $activityId): void
    {
        $this->commandBus->dispatch(new MarkActivityNotDone($this->slotId, $this->date, $activityId));
        $this->detail = null;
        $this->dispatchBrowserEvent('session:changed');
    }

    #[LiveAction]
    public function deleteActivity(#[LiveArg] string $activityId): void
    {
        $this->commandBus->dispatch(new RemoveActivityFromSession($this->slotId, $this->date, $activityId));
        $this->detail = null;
        $this->dispatchBrowserEvent('session:changed');
    }
}
