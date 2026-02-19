<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Dto;

use Common\Module\Health\Application\Enum\IncidentSeverityEnum;
use Common\Module\Health\Application\Enum\IncidentStatusEnum;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * DTO для инцидента.
 */
final readonly class IncidentDto
{
    /**
     * @param array<int, string> $affectedServiceNames
     */
    public function __construct(
        public Uuid $uuid,
        public string $title,
        public ?string $description,
        public IncidentStatusEnum $status,
        public IncidentSeverityEnum $severity,
        public array $affectedServiceNames,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $resolvedAt,
    ) {
    }
}
