<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Mapper;

use Common\Module\Health\Application\Enum\IncidentStatusEnum as ApplicationIncidentStatusEnum;
use Common\Module\Health\Domain\Enum\IncidentStatusEnum as DomainIncidentStatusEnum;

/**
 * Маппер для конвертации Domain IncidentStatusEnum в Application IncidentStatusEnum.
 */
final readonly class IncidentStatusMapper
{
    public function map(DomainIncidentStatusEnum $status): ApplicationIncidentStatusEnum
    {
        return ApplicationIncidentStatusEnum::from($status->value);
    }
}
