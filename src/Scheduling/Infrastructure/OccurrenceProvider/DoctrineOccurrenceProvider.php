<?php

declare(strict_types=1);

namespace App\Scheduling\Infrastructure\OccurrenceProvider;

use App\Scheduling\Domain\DayOfWeek;
use App\Scheduling\Domain\OccurrenceCalculator;
use App\Scheduling\Domain\ScheduledSlot;
use App\Scheduling\Infrastructure\Doctrine\Entity\TimetableSlotEntity;
use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Occurrence;
use App\Shared\Domain\Port\OccurrenceProvider;
use App\Shared\Domain\TimeRange;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(OccurrenceProvider::class)]
final class DoctrineOccurrenceProvider implements OccurrenceProvider
{
    /**
     * @var list<ScheduledSlot>|null
     */
    private ?array $slots = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OccurrenceCalculator $calculator,
    ) {
    }

    public function forDay(DateTimeImmutable $date): array
    {
        return $this->calculator->forDay($this->loadSlots(), $date);
    }

    public function resolve(SlotId $slotId, DateTimeImmutable $date): ?Occurrence
    {
        return $this->calculator->resolve($this->loadSlots(), $slotId, $date);
    }

    public function nextAfter(ClassroomId $classroomId, DateTimeImmutable $after): ?Occurrence
    {
        return $this->calculator->nextAfter($this->loadSlots(), $classroomId, $after);
    }

    /**
     * @return list<ScheduledSlot>
     */
    private function loadSlots(): array
    {
        return $this->slots ??= $this->fetchSlots();
    }

    /**
     * @return list<ScheduledSlot>
     */
    private function fetchSlots(): array
    {
        /** @var list<TimetableSlotEntity> $rows */
        $rows = $this->em->getRepository(TimetableSlotEntity::class)
            ->createQueryBuilder('s')
            ->addSelect('c')
            ->join('s.classroom', 'c')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (TimetableSlotEntity $e): ScheduledSlot => new ScheduledSlot(
                SlotId::fromString($e->id),
                ClassroomId::fromString($e->classroom->id),
                $e->classroom->name,
                DayOfWeek::from($e->dayOfWeek),
                new TimeRange($e->startMinute, $e->endMinute),
                $e->subject,
                $e->room,
                $e->validFrom,
                $e->validTo,
            ),
            $rows,
        );
    }
}
