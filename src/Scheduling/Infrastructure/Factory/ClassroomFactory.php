<?php

declare(strict_types=1);

namespace App\Scheduling\Infrastructure\Factory;

use App\Scheduling\Infrastructure\Doctrine\Entity\ClassroomEntity;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ClassroomEntity>
 */
final class ClassroomFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ClassroomEntity::class;
    }

    protected function defaults(): array
    {
        return [
            'id' => (string) Uuid::v7(),
            'name' => self::faker()->randomElement(['6e A', '5e B', '4e C', '3e A']),
        ];
    }
}
