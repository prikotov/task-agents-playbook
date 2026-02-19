<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Repository\ServiceStatus\Criteria;

// phpcs:disable
use Common\Exception\ConfigurationException;
use Common\Module\Health\Domain\Repository\ServiceStatus\Criteria\ServiceStatusFindCriteria;
use Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusCriteriaInterface;
use Common\Module\Health\Infrastructure\Repository\ServiceStatus\Criteria\Mapper\ServiceStatusFindCriteriaMapper;
use Common\Module\Health\Infrastructure\Repository\ServiceStatus\ServiceStatusRepository;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;

// phpcs:enable

/**
 * Маппер критериев для ServiceStatusRepository.
 */
final readonly class CriteriaMapper
{
    public function __construct(
        private ServiceStatusFindCriteriaMapper $findCriteriaMapper,
    ) {
    }

    /**
     * @throws QueryException
     */
    public function map(
        ServiceStatusRepository $repository,
        ServiceStatusCriteriaInterface $criteria,
    ): QueryBuilder {
        return match (get_class($criteria)) {
            ServiceStatusFindCriteria::class
                => $this->findCriteriaMapper->map($repository, $criteria),
            default => throw new ConfigurationException('Not found criteria mapper for ' . get_class($criteria)),
        };
    }
}
