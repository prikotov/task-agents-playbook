<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Dto;

use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use Common\Module\Health\Application\Enum\ServiceStatusEnum;
use DateTimeImmutable;

/**
 * DTO для статуса отдельного сервиса.
 */
final readonly class ServiceHealthDto
{
    public function __construct(
        public string $name,
        public ServiceStatusEnum $status,
        public ServiceCategoryEnum $category,
        public ?DateTimeImmutable $lastCheckAt = null,
        public ?string $message = null,
        public ?float $responseTimeMs = null,
    ) {
    }
}
