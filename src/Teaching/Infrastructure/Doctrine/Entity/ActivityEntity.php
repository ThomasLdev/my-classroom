<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Doctrine\Entity;

use App\Teaching\Domain\Model\Session\ActivityStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'teaching_activity')]
class ActivityEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    public string $id;

    #[ORM\ManyToOne(targetEntity: SessionEntity::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public SessionEntity $session;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $title;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: ActivityStatus::class)]
    public ActivityStatus $status;

    #[ORM\Column(type: Types::INTEGER)]
    public int $position;

    #[ORM\Column(name: 'carried_over_from', type: Types::GUID, nullable: true)]
    public ?string $carriedOverFrom = null;
}
