<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Repository\ServiceStatus;

use Common\Exception\InfrastructureException;
use Common\Exception\NotFoundException;
use Common\Module\Health\Domain\Entity\ServiceStatusModel;
use Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusCriteriaInterface;
use Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusRepositoryInterface;
use Common\Module\Health\Infrastructure\Repository\ServiceStatus\Criteria\CriteriaMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * PostgreSQL репозиторий для хранения статусов сервисов.
 */
final class ServiceStatusRepository extends ServiceEntityRepository implements ServiceStatusRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CriteriaMapper $criteriaMapper,
    ) {
        parent::__construct($registry, ServiceStatusModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getById(?int $id = null, ?Uuid $uuid = null): ServiceStatusModel
    {
        if ($id === null && $uuid === null) {
            throw new InvalidArgumentException(
                sprintf('Either an ID or a UUID must be provided for entity %s.', $this->getEntityName()),
            );
        }

        if ($id !== null) {
            return $this->find($id) ?? throw new NotFoundException(sprintf(
                'Cannot find %s with id %s',
                $this->getEntityName(),
                $id,
            ));
        }

        if ($uuid !== null) {
            return $this->createQueryBuilder('s')
                ->andWhere('s.uuid = :uuid')
                ->setParameter('uuid', $uuid, UuidType::NAME)
                ->getQuery()
                ->getOneOrNullResult() ?? throw new NotFoundException(sprintf(
                    'Cannot find %s with uuid %s',
                    $this->getEntityName(),
                    $uuid,
                ));
        }

        throw new NotFoundException(sprintf('%s not found', $this->getEntityName()));
    }

    public function getOneByCriteria(ServiceStatusCriteriaInterface $criteria): ?ServiceStatusModel
    {
        return $this
            ->getQueryBuilderByCriteria($criteria)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * @inheritDoc
     */
    public function getByCriteria(ServiceStatusCriteriaInterface $criteria): array
    {
        return $this
            ->getQueryBuilderByCriteria($criteria)
            ->getQuery()
            ->getResult();
    }

    public function getCountByCriteria(ServiceStatusCriteriaInterface $criteria): int
    {
        return $this
            ->getQueryBuilderByCriteria($criteria)
            ->select('count(1) as count')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function exists(ServiceStatusCriteriaInterface $criteria): bool
    {
        return (bool)$this
            ->getQueryBuilderByCriteria($criteria)
            ->select('1')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(ServiceStatusModel $serviceStatus): void
    {
        $this->getEntityManager()->persist($serviceStatus);
        $this->getEntityManager()->flush();
    }

    public function delete(ServiceStatusModel $serviceStatus): void
    {
        $this->getEntityManager()->remove($serviceStatus);
        $this->getEntityManager()->flush();
    }

    private function getQueryBuilderByCriteria(ServiceStatusCriteriaInterface $criteria): QueryBuilder
    {
        try {
            $qb = $this->criteriaMapper->map($this, $criteria);
        } catch (QueryException $exception) {
            throw new InfrastructureException(
                message: sprintf('Failed to build query for %s: %s', $this->getEntityName(), $exception->getMessage()),
                previous: $exception,
            );
        }

        return $qb;
    }
}
