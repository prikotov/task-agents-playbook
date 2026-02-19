<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Repository\Incident\Criteria;

use Common\Component\Repository\CriteriaWithLimitInterface;
use Common\Component\Repository\CriteriaWithOffsetInterface;
use Common\Component\Repository\SortableCriteriaInterface;
use Common\Component\Repository\Trait\CriteriaWithLimitTrait;
use Common\Component\Repository\Trait\CriteriaWithOffsetTrait;
use Common\Component\Repository\Trait\SortableCriteriaTrait;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum;
use Common\Module\Health\Domain\Enum\IncidentStatusEnum;
use Common\Module\Health\Domain\Repository\Incident\IncidentCriteriaInterface;

/**
 * Критерии для поиска инцидентов.
 */
final class IncidentFindCriteria implements
    IncidentCriteriaInterface,
    SortableCriteriaInterface,
    CriteriaWithLimitInterface,
    CriteriaWithOffsetInterface
{
    use SortableCriteriaTrait;
    use CriteriaWithLimitTrait;
    use CriteriaWithOffsetTrait;

    public function __construct(
        private ?IncidentStatusEnum $status = null,
        private ?IncidentSeverityEnum $severity = null,
        private ?string $serviceName = null,
        private bool $activeOnly = false,
    ) {
    }

    public function getStatus(): ?IncidentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(?IncidentStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getSeverity(): ?IncidentSeverityEnum
    {
        return $this->severity;
    }

    public function setSeverity(?IncidentSeverityEnum $severity): self
    {
        $this->severity = $severity;
        return $this;
    }

    public function getServiceName(): ?string
    {
        return $this->serviceName;
    }

    public function setServiceName(?string $serviceName): self
    {
        $this->serviceName = $serviceName;
        return $this;
    }

    public function isActiveOnly(): bool
    {
        return $this->activeOnly;
    }

    public function setActiveOnly(bool $activeOnly): self
    {
        $this->activeOnly = $activeOnly;
        return $this;
    }
}
