<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Doctrine\Mapper;

use App\Teaching\Domain\Model\Session\ActivityStatus;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Infrastructure\Doctrine\Entity\ActivityEntity;
use App\Teaching\Infrastructure\Doctrine\Entity\SessionEntity;

/**
 * @phpstan-import-type ActivityStateArray from \App\Teaching\Domain\Model\Session\Activity
 */
final class SessionMapper
{
    public function toDomain(SessionEntity $entity): Session
    {
        return Session::fromState([
            'id' => $entity->id,
            'classroomId' => $entity->classroomId,
            'slotId' => $entity->slotId,
            'date' => $entity->date->format('Y-m-d'),
            'startMinute' => $entity->startMinute,
            'endMinute' => $entity->endMinute,
            'closedAt' => $entity->closedAt?->format(\DateTimeInterface::ATOM),
            'cancelled' => $entity->cancelled,
            'activities' => array_map(
                static fn (ActivityEntity $a): array => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'status' => $a->status->value,
                    'position' => $a->position,
                    'carriedOverFrom' => $a->carriedOverFrom,
                ],
                array_values($entity->activities->toArray()),
            ),
        ]);
    }

    public function toEntity(Session $session, SessionEntity $entity): SessionEntity
    {
        $state = $session->toState();

        $entity->id = $state['id'];
        $entity->classroomId = $state['classroomId'];
        $entity->slotId = $state['slotId'];
        $entity->date = new \DateTimeImmutable($state['date']);
        $entity->startMinute = $state['startMinute'];
        $entity->endMinute = $state['endMinute'];
        $entity->closedAt = $state['closedAt'] !== null ? new \DateTimeImmutable($state['closedAt']) : null;
        $entity->cancelled = $state['cancelled'];

        $this->reconcileActivities($state['activities'], $entity);

        return $entity;
    }

    /**
     * Manual collection sync so Doctrine identity / orphanRemoval stays correct.
     *
     * @param list<ActivityStateArray> $activities
     */
    private function reconcileActivities(array $activities, SessionEntity $entity): void
    {
        /** @var array<string, ActivityEntity> $existing */
        $existing = [];
        foreach ($entity->activities as $activityEntity) {
            $existing[$activityEntity->id] = $activityEntity;
        }

        $kept = [];
        foreach ($activities as $activity) {
            $id = $activity['id'];
            $kept[$id] = true;

            $activityEntity = $existing[$id] ?? new ActivityEntity();
            $activityEntity->id = $id;
            $activityEntity->session = $entity;
            $activityEntity->title = $activity['title'];
            $activityEntity->status = ActivityStatus::from($activity['status']);
            $activityEntity->position = $activity['position'];
            $activityEntity->carriedOverFrom = $activity['carriedOverFrom'];

            if (!isset($existing[$id])) {
                $entity->activities->add($activityEntity);
            }
        }

        foreach ($existing as $id => $activityEntity) {
            if (!isset($kept[$id])) {
                $entity->activities->removeElement($activityEntity);
            }
        }
    }
}
