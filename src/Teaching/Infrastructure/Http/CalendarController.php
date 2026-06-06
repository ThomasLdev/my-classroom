<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Http;

use App\Teaching\Application\Command\AttachDocumentToSession\AttachDocumentToSession;
use App\Teaching\Application\Command\RemoveDocumentFromSession\RemoveDocumentFromSession;
use App\Teaching\Application\Port\DocumentStorage;
use App\Teaching\Application\Query\GetDayView\DayView;
use App\Teaching\Application\Query\GetDayView\GetDayView;
use App\Teaching\Application\Query\GetSessionDetail\DocumentView;
use App\Teaching\Application\Query\GetSessionDetail\GetSessionDetail;
use App\Teaching\Application\Query\GetSessionDetail\SessionDetailView;
use App\Teaching\Application\Query\GetWeek\GetWeek;
use App\Teaching\Application\Query\GetWeek\WeekView;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use App\Teaching\Infrastructure\Http\Form\AttachDocumentType;
use App\Teaching\Infrastructure\Http\Form\DeleteDocumentType;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

// 15 MB per file: enough for a worksheet or a photo, bounded to keep local storage sane.

final class CalendarController extends AbstractController
{
    use HandleTrait;

    private const int MAX_UPLOAD_BYTES = 15 * 1024 * 1024;

    public function __construct(
        #[Autowire(service: 'query.bus')]
        MessageBusInterface $queryBus,
        #[Autowire(service: 'command.bus')]
        private readonly MessageBusInterface $commandBus,
        private readonly DocumentStorage $storage,
        private readonly CalendarPresenter $presenter,
    ) {
        $this->messageBus = $queryBus;
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): RedirectResponse
    {
        return $this->redirectToRoute('app_calendar_day', [
            'date' => new DateTimeImmutable('today')->format('Y-m-d'),
        ]);
    }

    #[Route('/calendar/{date}', name: 'app_calendar_day', requirements: [
        'date' => '\d{4}-\d{2}-\d{2}',
    ], methods: ['GET'])]
    public function day(string $date): Response
    {
        $selected = $this->selectedDate($date);
        $today = new DateTimeImmutable('today');

        /** @var DayView $day */
        $day = $this->handle(new GetDayView($selected->format('Y-m-d')));

        /** @var WeekView $weekView */
        $weekView = $this->handle(new GetWeek($selected->format('Y-m-d')));
        $dotsByDate = [];
        foreach ($weekView->days as $weekDay) {
            $dotsByDate[$weekDay->date] = [
                'count' => count($weekDay->classroomNames),
                'hasEvent' => $weekDay->hasEvent,
            ];
        }

        return $this->render('calendar/day.html.twig', [
            'day' => $day,
            'header' => $this->presenter->header($selected, $today),
            'week' => $this->presenter->week($selected, $today, $dotsByDate),
            'prevDate' => $selected->modify('-1 day')->format('Y-m-d'),
            'nextDate' => $selected->modify('+1 day')->format('Y-m-d'),
        ]);
    }

    #[Route('/calendar/{date}/session/{slotId}', name: 'app_session_detail', requirements: [
        'date' => '\d{4}-\d{2}-\d{2}',
    ], methods: ['GET'])]
    public function session(string $date, string $slotId, Request $request): Response
    {
        try {
            /** @var SessionDetailView $detail */
            $detail = $this->handle(new GetSessionDetail($slotId, $date));
        } catch (SlotNotScheduled $e) {
            throw $this->createNotFoundException(previous: $e);
        }

        $selected = $this->selectedDate($date);

        $uploadForm = $this->createForm(AttachDocumentType::class, null, [
            'action' => $this->generateUrl('app_session_document_add', [
                'date' => $date,
                'slotId' => $slotId,
            ]),
            'attr' => [
                'data-turbo-frame' => 'sheet',
            ],
        ]);

        $deleteForms = [];
        foreach ($detail->documents as $document) {
            $deleteForms[$document->id] = $this->createForm(DeleteDocumentType::class, null, [
                'action' => $this->generateUrl('app_session_document_remove', [
                    'date' => $date,
                    'slotId' => $slotId,
                    'documentId' => $document->id,
                ]),
                'attr' => [
                    'data-turbo-frame' => 'sheet',
                ],
            ])->createView();
        }

        return $this->render('calendar/session.html.twig', [
            'detail' => $detail,
            'header' => $this->presenter->header($selected, new DateTimeImmutable('today')),
            'backDate' => $date,
            'uploadForm' => $uploadForm->createView(),
            'deleteForms' => $deleteForms,
            // Set after a document mutation so the day cards (their doc indicator) refresh.
            'refreshDay' => $request->query->getBoolean('refreshDay'),
        ]);
    }

    #[Route('/calendar/{date}/session/{slotId}/document', name: 'app_session_document_add', requirements: [
        'date' => '\d{4}-\d{2}-\d{2}',
    ], methods: ['POST'])]
    public function addDocument(string $date, string $slotId, Request $request): RedirectResponse
    {
        $form = $this->createForm(AttachDocumentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var list<UploadedFile> $files */
            $files = $form->get('document')->getData() ?? [];
            foreach ($files as $file) {
                if (! $file->isValid()) {
                    continue;
                }
                $size = (int) $file->getSize();
                if ($size > self::MAX_UPLOAD_BYTES) {
                    continue;
                }
                $this->commandBus->dispatch(new AttachDocumentToSession(
                    $slotId,
                    $date,
                    $file->getClientOriginalName(),
                    $size,
                    $file->getClientMimeType(),
                    $file->getPathname(),
                ));
            }
        }

        return $this->redirectToRoute('app_session_detail', [
            'date' => $date,
            'slotId' => $slotId,
            'refreshDay' => 1,
        ]);
    }

    #[Route('/calendar/{date}/session/{slotId}/document/{documentId}', name: 'app_session_document_remove', requirements: [
        'date' => '\d{4}-\d{2}-\d{2}',
    ], methods: ['POST'])]
    public function removeDocument(string $date, string $slotId, string $documentId, Request $request): RedirectResponse
    {
        $form = $this->createForm(DeleteDocumentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new RemoveDocumentFromSession($slotId, $date, $documentId));
        }

        return $this->redirectToRoute('app_session_detail', [
            'date' => $date,
            'slotId' => $slotId,
            'refreshDay' => 1,
        ]);
    }

    #[Route('/calendar/{date}/session/{slotId}/document/{documentId}/download', name: 'app_session_document_download', requirements: [
        'date' => '\d{4}-\d{2}-\d{2}',
    ], methods: ['GET'])]
    public function downloadDocument(string $date, string $slotId, string $documentId): BinaryFileResponse
    {
        try {
            /** @var SessionDetailView $detail */
            $detail = $this->handle(new GetSessionDetail($slotId, $date));
        } catch (SlotNotScheduled $e) {
            throw $this->createNotFoundException(previous: $e);
        }

        $document = null;
        foreach ($detail->documents as $candidate) {
            if ($candidate->id === $documentId) {
                $document = $candidate;
                break;
            }
        }

        $path = $this->storage->locate($documentId);
        if (! $document instanceof DocumentView || ! is_file($path)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $document->contentType);
        $response->setContentDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $document->name);

        return $response;
    }

    private function selectedDate(string $date): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed instanceof DateTimeImmutable ? $parsed : new DateTimeImmutable('today');
    }
}
