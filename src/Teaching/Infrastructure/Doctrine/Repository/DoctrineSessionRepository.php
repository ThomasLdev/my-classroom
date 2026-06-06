<?php

declare(strict_types=1);

namespace App\Teaching\Infrastructure\Doctrine\Repository;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Teaching\Domain\Model\Session\ActivityStatus;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;
use App\Teaching\Domain\Repository\SessionRepository;
use App\Teaching\Infrastructure\Doctrine\Entity\SessionEntity;
use App\Teaching\Infrastructure\Doctrine\Mapper\SessionMapper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SessionRepository::class)]
final class DoctrineSessionRepository implements SessionRepository
{
    /** @var EntityRepository<SessionEntity> */
    private readonly EntityRepository $entities;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SessionMapper $mapper,
    ) {
        $this->entities = $em->getRepository(SessionEntity::class);
    }

    public function ofId(SessionId $id): ?Session
    {
        $entity = $this->entities->find((string) $id);

        return $entity !== null ? $this->mapper->toDomain($entity) : null;
    }

    public function ofOccurrence(SlotId $slotId, \DateTimeImmutable $date): ?Session
    {
        $entity = $this->entities->findOneBy([
            'slotId' => (string) $slotId,
            'date' => $date->setTime(0, 0),
        ]);

        return $entity !== null ? $this->mapper->toDomain($entity) : null;
    }

    public function mostRecentUncheckedHomework(ClassroomId $classroomId, \DateTimeImmutable $before): ?Session
    {
        $entity = $this->entities->createQueryBuilder('s')
            ->andWhere('s.classroomId = :classroom')
            ->andWhere('s.date < :before')
            ->andWhere('s.homework IS NOT NULL')
            ->andWhere('s.homeworkChecked = false')
            ->setParameter('classroom', (string) $classroomId)
            ->setParameter('before', $before->setTime(0, 0))
            ->orderBy('s.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $entity !== null ? $this->mapper->toDomain($entity) : null;
    }

    public function save(Session $session): void
    {
        $entity = $this->entities->find((string) $session->id) ?? new SessionEntity();
        $this->mapper->toEntity($session, $entity);

        $this->em->persist($entity);
        // No flush: the doctrine_transaction middleware commits at the boundary.
    }

    public function elapsedOpenWithPlannedActivities(\DateTimeImmutable $now): array
    {
        // Elapsed (date + end minute <= now) is computed, so it is filtered in PHP after a narrow DB pre-filter.
        $candidates = $this->entities->createQueryBuilder('s')
            ->innerJoin('s.activities', 'a')
            ->andWhere('s.closedAt IS NULL')
            ->andWhere('a.status = :planned')
            ->setParameter('planned', ActivityStatus::Planned->value)
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.endMinute', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult();

        $elapsed = [];
        foreach ($candidates as $entity) {
            $session = $this->mapper->toDomain($entity);
            if ($session->endsAt() <= $now) {
                $elapsed[] = $session;
            }
        }

        return $elapsed;
    }
}
