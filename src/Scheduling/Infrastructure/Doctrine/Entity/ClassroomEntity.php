<?php

declare(strict_types=1);

namespace App\Scheduling\Infrastructure\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'scheduling_classroom')]
class ClassroomEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    public string $id;

    #[ORM\Column(length: 120)]
    public string $name;
}
