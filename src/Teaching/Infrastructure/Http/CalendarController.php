<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Http;

use App\Teaching\Application\Query\GetDayView\DayView;
use App\Teaching\Application\Query\GetDayView\GetDayView;
use App\Teaching\Application\Query\GetSessionDetail\GetSessionDetail;
use App\Teaching\Application\Query\GetSessionDetail\SessionDetailView;
use App\Teaching\Domain\Exception\SlotNotScheduled;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarController extends AbstractController
{
    use HandleTrait;

    private const array DOW = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    private const array DOW_SHORT = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    private const array MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    public function __construct(
        #[Autowire(service: 'query.bus')] MessageBusInterface $queryBus,
    ) {
        $this->messageBus = $queryBus;
    }

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->redirectToRoute('app_calendar_day', [
            'date' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
        ]);
    }

    #[Route('/calendar/{date}', name: 'app_calendar_day', requirements: ['date' => '\d{4}-\d{2}-\d{2}'], methods: ['GET'])]
    public function day(string $date): Response
    {
        $selected = \DateTimeImmutable::createFromFormat('!Y-m-d', $date) ?: new \DateTimeImmutable('today');
        $today = new \DateTimeImmutable('today');

        /** @var DayView $day */
        $day = $this->handle(new GetDayView($selected->format('Y-m-d')));

        // Stable pastel key per classroom (no color in the domain yet).
        $colorByClass = [];
        foreach ($day->sessions as $session) {
            $colorByClass[$session->classroomName] ??= 'k'.(crc32($session->classroomName) % 4 + 1);
        }

        $monday = $selected->modify('monday this week');
        $week = [];
        for ($i = 0; $i < 7; ++$i) {
            $cursor = $monday->modify(sprintf('+%d days', $i));
            $weekday = (int) $cursor->format('N');
            $week[] = [
                'date' => $cursor->format('Y-m-d'),
                'dow' => self::DOW_SHORT[$weekday - 1],
                'num' => (int) $cursor->format('j'),
                'isSelected' => $cursor->format('Y-m-d') === $selected->format('Y-m-d'),
                'isToday' => $cursor->format('Y-m-d') === $today->format('Y-m-d'),
                'isWeekend' => $weekday >= 6,
            ];
        }

        $weekday = (int) $selected->format('N');

        return $this->render('calendar/day.html.twig', [
            'day' => $day,
            'header' => [
                'dow' => self::DOW[$weekday - 1],
                'num' => (int) $selected->format('j'),
                'month' => self::MONTHS[(int) $selected->format('n') - 1],
                'year' => (int) $selected->format('Y'),
                'isToday' => $selected->format('Y-m-d') === $today->format('Y-m-d'),
            ],
            'week' => $week,
            'colorByClass' => $colorByClass,
            'prevDate' => $selected->modify('-1 day')->format('Y-m-d'),
            'nextDate' => $selected->modify('+1 day')->format('Y-m-d'),
        ]);
    }

    #[Route('/calendar/{date}/session/{slotId}', name: 'app_session_detail', requirements: ['date' => '\d{4}-\d{2}-\d{2}'], methods: ['GET'])]
    public function session(string $date, string $slotId): Response
    {
        try {
            /** @var SessionDetailView $detail */
            $detail = $this->handle(new GetSessionDetail($slotId, $date));
        } catch (SlotNotScheduled) {
            throw $this->createNotFoundException();
        }

        $selected = \DateTimeImmutable::createFromFormat('!Y-m-d', $date) ?: new \DateTimeImmutable('today');
        $weekday = (int) $selected->format('N');

        return $this->render('calendar/session.html.twig', [
            'detail' => $detail,
            'header' => [
                'dow' => self::DOW[$weekday - 1],
                'num' => (int) $selected->format('j'),
                'month' => self::MONTHS[(int) $selected->format('n') - 1],
                'year' => (int) $selected->format('Y'),
            ],
            'backDate' => $date,
        ]);
    }
}
