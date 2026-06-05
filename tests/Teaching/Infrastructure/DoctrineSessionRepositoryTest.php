<?php

declare(strict_types=1);

namespace App\Tests\Teaching\Infrastructure;

use App\Shared\Domain\Identifier\ClassroomId;
use App\Shared\Domain\Identifier\SlotId;
use App\Shared\Domain\Occurrence;
use App\Shared\Domain\TimeRange;
use App\Teaching\Domain\Model\Session\ActivityId;
use App\Teaching\Domain\Model\Session\Session;
use App\Teaching\Domain\Model\Session\SessionId;
use App\Teaching\Domain\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Integration test: proves the SessionMapper + DoctrineSessionRepository do a
 * faithful persist -> reload round trip against a real PostgreSQL database,
 * including value objects and the activities collection reconciliation.
 */
final class DoctrineSessionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private SessionRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(SessionRepository::class);

        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testItRoundTripsAnAggregateWithItsActivities(): void
    {
        $session = Session::materialize(
            SessionId::fromString((string) Uuid::v7()),
            new Occurrence(
                SlotId::fromString((string) Uuid::v7()),
                ClassroomId::fromString((string) Uuid::v7()),
                new \DateTimeImmutable('2026-06-08'),
                TimeRange::fromLabels('09:00', '10:00'),
                '5e B',
            ),
        );
        $session->addActivity(ActivityId::fromString((string) Uuid::v7()), 'À finir');
        $done = $session->addActivity(ActivityId::fromString((string) Uuid::v7()), 'Déjà fait');
        $done->markDone();

        $this->repository->save($session);
        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->repository->ofOccurrence($session->slotId, $session->date);

        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->id->equals($session->id));
        self::assertSame('09:00', $reloaded->timeRange->startLabel());
        self::assertSame('10:00', $reloaded->timeRange->endLabel());
        self::assertSame(2, $reloaded->activityCount());
        self::assertSame(1, $reloaded->doneCount());
    }

    public function testItReconcilesTheCollectionOnUpdate(): void
    {
        $session = Session::materialize(
            SessionId::fromString((string) Uuid::v7()),
            new Occurrence(
                SlotId::fromString((string) Uuid::v7()),
                ClassroomId::fromString((string) Uuid::v7()),
                new \DateTimeImmutable('2026-06-09'),
                TimeRange::fromLabels('10:00', '11:00'),
                '4e A',
            ),
        );
        $session->addActivity(ActivityId::fromString((string) Uuid::v7()), 'Tâche A');
        $this->repository->save($session);
        $this->em->flush();
        $this->em->clear();

        // Reload, mutate (mark done + add a second activity), persist again.
        $reloaded = $this->repository->ofId($session->id);
        $reloaded->activities[0]->markDone();
        $reloaded->addActivity(ActivityId::fromString((string) Uuid::v7()), 'Tâche B');
        $this->repository->save($reloaded);
        $this->em->flush();
        $this->em->clear();

        $again = $this->repository->ofId($session->id);
        self::assertSame(2, $again->activityCount(), 'The new activity was inserted.');
        self::assertSame(1, $again->doneCount(), 'The status change was persisted.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}
