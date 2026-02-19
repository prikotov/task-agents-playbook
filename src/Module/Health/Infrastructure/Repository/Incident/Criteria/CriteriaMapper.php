<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Repository\Incident\Criteria;

// phpcs:disable
use Common\Exception\ConfigurationException;
use Common\Module\Health\Domain\Repository\Incident\Criteria\IncidentFindCriteria;
use Common\Module\Health\Domain\Repository\Incident\IncidentCriteriaInterface;
use Common\Module\Health\Infrastructure\Repository\Incident\Criteria\Mapper\IncidentFindCriteriaMapper;
use Common\Module\Health\Infrastructure\Repository\Incident\IncidentRepository;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;

// phpcs:enable

/**
 * Маппер критериев для IncidentRepository.
 */
final readonly class CriteriaMapper
{
    public function __construct(
        private IncidentFindCriteriaMapper $findCriteriaMapper,
    ) {
    }

    /**
     * @throws QueryException
     */
    public function map(
        IncidentRepository $repository,
        IncidentCriteriaInterface $criteria,
    ): QueryBuilder {
        return match (get_class($criteria)) {
            IncidentFindCriteria::class
                => $this->findCriteriaMapper->map($repository, $criteria),
            default => throw new ConfigurationException('Not found criteria mapper for ' . get_class($criteria)),
        };
    }
}
