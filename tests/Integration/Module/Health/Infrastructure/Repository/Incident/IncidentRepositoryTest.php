<?php

declare(strict_types=1);

namespace Common\Test\Integration\Module\Health\Infrastructure\Repository\Incident;

use Common\Component\Repository\Enum\SortEnum;
use Common\Component\Test\IntegrationTestCase;
use Common\Module\Health\Domain\Entity\IncidentModel;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum;
use Common\Module\Health\Domain\Enum\IncidentStatusEnum;
use Common\Module\Health\Domain\Repository\Incident\Criteria\IncidentFindCriteria;
use Common\Module\Health\Domain\Repository\Incident\IncidentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class IncidentRepositoryTest extends IntegrationTestCase
{
    private IncidentRepositoryInterface $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $container = self::getContainer();
        $this->repository = $container->get(IncidentRepositoryInterface::class);
        $this->em = $container->get(EntityManagerInterface::class);
        $this->em->createQuery('DELETE FROM ' . IncidentModel::class)->execute();
    }

    public function testSaveAndFindById(): void
    {
        $incident = new IncidentModel(
            title: 'Database outage',
            description: 'PostgreSQL is not responding',
            severity: IncidentSeverityEnum::critical,
            affectedServiceNames: ['database', 'api'],
        );

        $this->repository->save($incident);
        $this->em->flush();

        $found = $this->repository->getById(id: $incident->getId());
        self::assertSame('Database outage', $found->getTitle());
        self::assertSame('PostgreSQL is not responding', $found->getDescription());
        self::assertSame(IncidentStatusEnum::investigating, $found->getStatus());
        self::assertSame(IncidentSeverityEnum::critical, $found->getSeverity());
    }

    public function testSaveAndFindByUuid(): void
    {
        $incident = new IncidentModel(
            title: 'API degraded',
            severity: IncidentSeverityEnum::major,
        );

        $this->repository->save($incident);
        $this->em->flush();

        $found = $this->repository->getById(uuid: $incident->getUuid());
        self::assertSame('API degraded', $found->getTitle());
    }

    public function testDelete(): void
    {
        $incident = new IncidentModel(title: 'Temporary incident');

        $this->repository->save($incident);
        $this->em->flush();
        $id = $incident->getId();

        $this->repository->delete($incident);
        $this->em->flush();

        $this->expectException(\Common\Exception\NotFoundException::class);
        $this->repository->getById(id: $id);
    }

    public function testGetByCriteriaWithStatus(): void
    {
        $investigating = new IncidentModel(
            title: 'Investigating incident',
            severity: IncidentSeverityEnum::minor,
        );

        $resolved = new IncidentModel(
            title: 'Resolved incident',
            severity: IncidentSeverityEnum::minor,
        );
        $resolved->resolve();

        $this->repository->save($investigating);
        $this->repository->save($resolved);
        $this->em->flush();

        $criteria = new IncidentFindCriteria(
            status: IncidentStatusEnum::investigating,
        );
        $found = $this->repository->getByCriteria($criteria);

        self::assertCount(1, $found);
        self::assertSame('Investigating incident', $found[0]->getTitle());
    }

    public function testGetByCriteriaWithSeverity(): void
    {
        $critical = new IncidentModel(
            title: 'Critical incident',
            severity: IncidentSeverityEnum::critical,
        );

        $minor = new IncidentModel(
            title: 'Minor incident',
            severity: IncidentSeverityEnum::minor,
        );

        $this->repository->save($critical);
        $this->repository->save($minor);
        $this->em->flush();

        $criteria = new IncidentFindCriteria(
            severity: IncidentSeverityEnum::critical,
        );
        $found = $this->repository->getByCriteria($criteria);

        self::assertCount(1, $found);
        self::assertSame('Critical incident', $found[0]->getTitle());
    }

    public function testGetByCriteriaWithActiveOnly(): void
    {
        $active = new IncidentModel(
            title: 'Active incident',
            severity: IncidentSeverityEnum::minor,
        );

        $resolved = new IncidentModel(
            title: 'Resolved incident',
            severity: IncidentSeverityEnum::minor,
        );
        $resolved->resolve();

        $this->repository->save($active);
        $this->repository->save($resolved);
        $this->em->flush();

        $criteria = new IncidentFindCriteria(
            activeOnly: true,
        );
        $found = $this->repository->getByCriteria($criteria);

        self::assertCount(1, $found);
        self::assertSame('Active incident', $found[0]->getTitle());
    }

    public function testGetByCriteriaWithLimitAndOffset(): void
    {
        $first = new IncidentModel(
            title: 'First incident',
            severity: IncidentSeverityEnum::minor,
        );
        $second = new IncidentModel(
            title: 'Second incident',
            severity: IncidentSeverityEnum::minor,
        );
        $third = new IncidentModel(
            title: 'Third incident',
            severity: IncidentSeverityEnum::minor,
        );

        $this->repository->save($first);
        $this->repository->save($second);
        $this->repository->save($third);
        $this->em->flush();

        $criteria = new IncidentFindCriteria();
        $criteria->setLimit(2);
        $criteria->setOffset(1);
        $found = $this->repository->getByCriteria($criteria);

        self::assertCount(2, $found);
    }

    public function testGetByCriteriaWithSort(): void
    {
        $first = new IncidentModel(
            title: 'AAA incident',
            severity: IncidentSeverityEnum::minor,
        );
        $second = new IncidentModel(
            title: 'BBB incident',
            severity: IncidentSeverityEnum::minor,
        );

        $this->repository->save($first);
        $this->repository->save($second);
        $this->em->flush();

        $criteria = new IncidentFindCriteria();
        $criteria->setSort(['title' => SortEnum::asc]);
        $found = $this->repository->getByCriteria($criteria);

        self::assertCount(2, $found);
        self::assertSame('AAA incident', $found[0]->getTitle());
        self::assertSame('BBB incident', $found[1]->getTitle());
    }

    public function testGetCountByCriteria(): void
    {
        $first = new IncidentModel(
            title: 'First incident',
            severity: IncidentSeverityEnum::critical,
        );
        $second = new IncidentModel(
            title: 'Second incident',
            severity: IncidentSeverityEnum::minor,
        );

        $this->repository->save($first);
        $this->repository->save($second);
        $this->em->flush();

        $criteria = new IncidentFindCriteria(
            severity: IncidentSeverityEnum::critical,
        );
        $count = $this->repository->getCountByCriteria($criteria);

        self::assertSame(1, $count);
    }

    public function testExists(): void
    {
        $incident = new IncidentModel(
            title: 'Test incident',
            severity: IncidentSeverityEnum::critical,
        );

        $this->repository->save($incident);
        $this->em->flush();

        $criteria = new IncidentFindCriteria(
            severity: IncidentSeverityEnum::critical,
        );
        self::assertTrue($this->repository->exists($criteria));

        $criteria = new IncidentFindCriteria(
            severity: IncidentSeverityEnum::minor,
        );
        self::assertFalse($this->repository->exists($criteria));
    }

    public function testGetOneByCriteria(): void
    {
        $incident = new IncidentModel(
            title: 'Unique title incident',
            severity: IncidentSeverityEnum::major,
        );

        $this->repository->save($incident);
        $this->em->flush();

        $criteria = new IncidentFindCriteria(
            severity: IncidentSeverityEnum::major,
        );
        $found = $this->repository->getOneByCriteria($criteria);

        self::assertNotNull($found);
        self::assertSame('Unique title incident', $found->getTitle());
    }

    public function testResolveUpdatesStatus(): void
    {
        $incident = new IncidentModel(
            title: 'To be resolved',
            severity: IncidentSeverityEnum::minor,
        );

        $this->repository->save($incident);
        $this->em->flush();
        $incident->resolve();
        $this->repository->save($incident);
        $this->em->flush();

        $found = $this->repository->getById(id: $incident->getId());
        self::assertSame(IncidentStatusEnum::resolved, $found->getStatus());
        self::assertNotNull($found->getResolvedAt());
    }
}
