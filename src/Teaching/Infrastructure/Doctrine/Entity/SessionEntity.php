<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'teaching_session')]
#[ORM\UniqueConstraint(name: 'uniq_session_slot_date', columns: ['slot_id', 'date'])]
class SessionEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    public string $id;

    #[ORM\Column(name: 'classroom_id', type: Types::GUID)]
    public string $classroomId;

    #[ORM\Column(name: 'slot_id', type: Types::GUID, nullable: true)]
    public ?string $slotId = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    public DateTimeImmutable $date;

    #[ORM\Column(name: 'start_minute', type: Types::INTEGER)]
    public int $startMinute;

    #[ORM\Column(name: 'end_minute', type: Types::INTEGER)]
    public int $endMinute;

    #[ORM\Column(name: 'closed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $closedAt = null;

    #[ORM\Column(type: Types::BOOLEAN, options: [
        'default' => false,
    ])]
    public bool $cancelled = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $note = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $homework = null;

    #[ORM\Column(name: 'homework_checked', type: Types::BOOLEAN, options: [
        'default' => false,
    ])]
    public bool $homeworkChecked = false;

    /**
     * @var Collection<int, ActivityEntity>
     */
    #[ORM\OneToMany(
        targetEntity: ActivityEntity::class,
        mappedBy: 'session',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy([
        'position' => 'ASC',
    ])]
    public Collection $activities;

    /**
     * @var Collection<int, DocumentEntity>
     */
    #[ORM\OneToMany(
        targetEntity: DocumentEntity::class,
        mappedBy: 'session',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy([
        'id' => 'ASC',
    ])]
    public Collection $documents;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }
}
