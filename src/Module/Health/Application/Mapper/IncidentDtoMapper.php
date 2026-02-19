<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Mapper;

use Common\Module\Health\Application\Dto\IncidentDto;
use Common\Module\Health\Domain\Entity\IncidentModel;

/**
 * Mapper для преобразования IncidentModel в IncidentDto.
 */
final readonly class IncidentDtoMapper
{
    public function __construct(
        private IncidentStatusMapper $statusMapper,
        private IncidentSeverityMapper $severityMapper,
    ) {
    }

    /**
     * Преобразует модель в DTO.
     */
    public function map(IncidentModel $model): IncidentDto
    {
        return new IncidentDto(
            uuid: $model->getUuid(),
            title: $model->getTitle(),
            description: $model->getDescription(),
            status: $this->statusMapper->map($model->getStatus()),
            severity: $this->severityMapper->map($model->getSeverity()),
            affectedServiceNames: array_map(
                static fn($name) => $name->value,
                $model->getAffectedServiceNames(),
            ),
            createdAt: $model->getInsTs(),
            updatedAt: $model->getUpdTs(),
            resolvedAt: $model->getResolvedAt(),
        );
    }
}
