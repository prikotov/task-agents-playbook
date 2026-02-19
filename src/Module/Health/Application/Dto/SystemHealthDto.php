<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Dto;

use Common\Module\Health\Application\Enum\ServiceStatusEnum;
use DateTimeImmutable;

/**
 * DTO для общего статуса системы.
 */
final readonly class SystemHealthDto
{
    /**
     * @param ServiceHealthDto[] $services
     */
    public function __construct(
        public ServiceStatusEnum $overallStatus,
        public array $services,
        public DateTimeImmutable $checkedAt,
        public int $totalServices = 0,
        public int $operationalCount = 0,
        public int $degradedCount = 0,
        public int $outageCount = 0,
        public int $maintenanceCount = 0,
    ) {
    }
}
