<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'teaching_document')]
class DocumentEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    public string $id;

    #[ORM\ManyToOne(targetEntity: SessionEntity::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public SessionEntity $session;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public string $name;

    #[ORM\Column(type: Types::INTEGER)]
    public int $size;

    #[ORM\Column(name: 'content_type', type: Types::STRING, length: 150)]
    public string $contentType;
}
