<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Mapper;

use Common\Module\Health\Application\Enum\IncidentSeverityEnum as ApplicationIncidentSeverityEnum;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum as DomainIncidentSeverityEnum;

/**
 * Маппер для конвертации Domain IncidentSeverityEnum в Application IncidentSeverityEnum.
 */
final readonly class IncidentSeverityMapper
{
    public function map(DomainIncidentSeverityEnum $severity): ApplicationIncidentSeverityEnum
    {
        return ApplicationIncidentSeverityEnum::from($severity->value);
    }
}
