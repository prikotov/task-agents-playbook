<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Repository\Incident;

use Common\Exception\InfrastructureException;
use Common\Exception\NotFoundException;
use Common\Module\Health\Domain\Entity\IncidentModel;
use Common\Module\Health\Domain\Repository\Incident\IncidentCriteriaInterface;
use Common\Module\Health\Domain\Repository\Incident\IncidentRepositoryInterface;
use Common\Module\Health\Infrastructure\Repository\Incident\Criteria\CriteriaMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Override;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * PostgreSQL репозиторий для хранения инцидентов.
 */
final class IncidentRepository extends ServiceEntityRepository implements IncidentRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CriteriaMapper $criteriaMapper,
    ) {
        parent::__construct($registry, IncidentModel::class);
    }

    #[Override]
    public function getById(?int $id = null, ?Uuid $uuid = null): IncidentModel
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
            return $this->createQueryBuilder('i')
                ->andWhere('i.uuid = :uuid')
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

    #[Override]
    public function getOneByCriteria(IncidentCriteriaInterface $criteria): ?IncidentModel
    {
        return $this
            ->getQueryBuilderByCriteria($criteria)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    #[Override]
    public function getByCriteria(IncidentCriteriaInterface $criteria): array
    {
        return $this
            ->getQueryBuilderByCriteria($criteria)
            ->getQuery()
            ->getResult();
    }

    #[Override]
    public function getCountByCriteria(IncidentCriteriaInterface $criteria): int
    {
        return $this
            ->getQueryBuilderByCriteria($criteria)
            ->select('count(1) as count')
            ->getQuery()
            ->getSingleScalarResult();
    }

    #[Override]
    public function exists(IncidentCriteriaInterface $criteria): bool
    {
        return (bool)$this
            ->getQueryBuilderByCriteria($criteria)
            ->select('1')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    #[Override]
    public function save(IncidentModel $incident): void
    {
        $this->getEntityManager()->persist($incident);
    }

    #[Override]
    public function delete(IncidentModel $incident): void
    {
        $this->getEntityManager()->remove($incident);
    }

    private function getQueryBuilderByCriteria(IncidentCriteriaInterface $criteria): QueryBuilder
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
