<?php

declare(strict_types=1);

namespace App\Scheduling\Infrastructure\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'scheduling_timetable_slot')]
class TimetableSlotEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    public string $id;

    #[ORM\ManyToOne(targetEntity: ClassroomEntity::class)]
    #[ORM\JoinColumn(name: 'classroom_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public ClassroomEntity $classroom;

    #[ORM\Column(name: 'day_of_week', type: Types::SMALLINT)]
    public int $dayOfWeek;

    #[ORM\Column(name: 'start_minute', type: Types::INTEGER)]
    public int $startMinute;

    #[ORM\Column(name: 'end_minute', type: Types::INTEGER)]
    public int $endMinute;

    #[ORM\Column(length: 80)]
    public string $subject;

    #[ORM\Column(length: 40, nullable: true)]
    public ?string $room = null;

    #[ORM\Column(name: 'valid_from', type: Types::DATE_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column(name: 'valid_to', type: Types::DATE_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $validTo = null;
}
