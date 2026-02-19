<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Repository\ServiceStatus\Criteria\Mapper;

use Common\Component\Repository\Criteria\Mapper\LimitOffsetSortCriteriaMapper;
use Common\Module\Health\Domain\Repository\ServiceStatus\Criteria\ServiceStatusFindCriteria;
use Common\Module\Health\Infrastructure\Repository\ServiceStatus\ServiceStatusRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;

/**
 * Маппер критериев поиска для ServiceStatusRepository.
 */
final readonly class ServiceStatusFindCriteriaMapper
{
    public function __construct(
        private LimitOffsetSortCriteriaMapper $limitOffsetSortCriteriaMapper,
    ) {
    }

    /**
     * @throws QueryException
     */
    public function map(
        ServiceStatusRepository $repository,
        ServiceStatusFindCriteria $criteria,
    ): QueryBuilder {
        $qb = $repository->createQueryBuilder('s');

        $status = $criteria->getStatus();
        if ($status !== null) {
            $qb->andWhere('s.status = :status');
            $qb->setParameter('status', $status->value, ParameterType::STRING);
        }

        $category = $criteria->getCategory();
        if ($category !== null) {
            $qb->andWhere('s.category = :category');
            $qb->setParameter('category', $category->value, ParameterType::STRING);
        }

        $name = $criteria->getName();
        if ($name !== null) {
            $qb->andWhere('s.name = :name');
            $qb->setParameter('name', $name, ParameterType::STRING);
        }

        $criteriaObject = $this->limitOffsetSortCriteriaMapper->map($criteria);
        $qb->addCriteria($criteriaObject);

        return $qb;
    }
}
