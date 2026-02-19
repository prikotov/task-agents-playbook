<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Repository\Incident\Criteria\Mapper;

use Common\Component\Repository\Criteria\Mapper\LimitOffsetSortCriteriaMapper;
use Common\Module\Health\Domain\Repository\Incident\Criteria\IncidentFindCriteria;
use Common\Module\Health\Infrastructure\Repository\Incident\IncidentRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;

/**
 * Маппер критериев поиска для IncidentRepository.
 */
final readonly class IncidentFindCriteriaMapper
{
    public function __construct(
        private LimitOffsetSortCriteriaMapper $limitOffsetSortCriteriaMapper,
    ) {
    }

    /**
     * @throws QueryException
     */
    public function map(
        IncidentRepository $repository,
        IncidentFindCriteria $criteria,
    ): QueryBuilder {
        $qb = $repository->createQueryBuilder('i');

        $status = $criteria->getStatus();
        if ($status !== null) {
            $qb->andWhere('i.status = :status');
            $qb->setParameter('status', $status->value, ParameterType::STRING);
        }

        $severity = $criteria->getSeverity();
        if ($severity !== null) {
            $qb->andWhere('i.severity = :severity');
            $qb->setParameter('severity', $severity->value, ParameterType::STRING);
        }

        $serviceName = $criteria->getServiceName();
        if ($serviceName !== null) {
            $qb->andWhere('JSON_CONTAINS(i.affectedServiceNames, :serviceName) = true');
            $qb->setParameter('serviceName', json_encode($serviceName, JSON_THROW_ON_ERROR));
        }

        $activeOnly = $criteria->isActiveOnly();
        if ($activeOnly) {
            $qb->andWhere('i.resolvedAt IS NULL');
        }

        $criteriaObject = $this->limitOffsetSortCriteriaMapper->map($criteria);
        $qb->addCriteria($criteriaObject);

        return $qb;
    }
}
