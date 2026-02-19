<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList;

use Common\Application\Dto\PaginationDto;
use Common\Application\Dto\SortDto;
use Common\Application\Query\QueryInterface;
use Common\Module\Health\Application\Enum\IncidentSeverityEnum;
use Common\Module\Health\Application\Enum\IncidentStatusEnum;

/**
 * @implements QueryInterface<IncidentListDto>
 */
final readonly class GetIncidentListQuery implements QueryInterface
{
    public function __construct(
        public ?IncidentStatusEnum $status = null,
        public ?IncidentSeverityEnum $severity = null,
        public ?string $serviceName = null,
        public bool $activeOnly = false,
        public PaginationDto $pagination = new PaginationDto(limit: 20),
        public ?SortDto $sort = null,
    ) {
    }
}
