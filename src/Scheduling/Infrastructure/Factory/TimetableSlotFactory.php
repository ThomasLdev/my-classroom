<?php

declare(strict_types=1);

namespace App\Scheduling\Infrastructure\Factory;

use App\Scheduling\Infrastructure\Doctrine\Entity\TimetableSlotEntity;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<TimetableSlotEntity>
 */
final class TimetableSlotFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return TimetableSlotEntity::class;
    }

    protected function defaults(): array
    {
        return [
            'id' => (string) Uuid::v7(),
            'classroom' => ClassroomFactory::new(),
            'dayOfWeek' => self::faker()->numberBetween(1, 5),
            'startMinute' => 8 * 60,
            'endMinute' => 9 * 60,
            'validFrom' => null,
            'validTo' => null,
        ];
    }
}
