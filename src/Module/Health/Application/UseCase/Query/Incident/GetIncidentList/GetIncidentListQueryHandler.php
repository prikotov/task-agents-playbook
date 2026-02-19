<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList;

use Common\Application\Mapper\SortDtoToOrderMapper;
use Common\Module\Health\Application\Mapper\IncidentDtoMapper;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum as DomainIncidentSeverityEnum;
use Common\Module\Health\Domain\Enum\IncidentStatusEnum as DomainIncidentStatusEnum;
use Common\Module\Health\Domain\Repository\Incident\Criteria\IncidentFindCriteria;
use Common\Module\Health\Domain\Repository\Incident\IncidentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetIncidentListQueryHandler
{
    public function __construct(
        private IncidentRepositoryInterface $repository,
        private IncidentDtoMapper $mapper,
        private SortDtoToOrderMapper $sortMapper,
    ) {
    }

    public function __invoke(GetIncidentListQuery $query): IncidentListDto
    {
        $criteria = new IncidentFindCriteria(
            status: $query->status !== null
                ? DomainIncidentStatusEnum::from($query->status->value)
                : null,
            severity: $query->severity !== null
                ? DomainIncidentSeverityEnum::from($query->severity->value)
                : null,
            serviceName: $query->serviceName,
            activeOnly: $query->activeOnly,
        );

        if ($query->sort !== null) {
            $criteria->setSort($this->sortMapper->map($query->sort));
        }

        $criteria->setLimit($query->pagination->limit);
        $criteria->setOffset($query->pagination->offset);

        $incidents = $this->repository->getByCriteria($criteria);
        $total = $this->repository->getCountByCriteria($criteria);

        $items = array_map(
            fn($incident) => $this->mapper->map($incident),
            $incidents,
        );

        $page = (int) floor($query->pagination->offset / $query->pagination->limit) + 1;

        return new IncidentListDto(
            items: $items,
            total: $total,
            page: $page,
            perPage: $query->pagination->limit,
        );
    }
}
