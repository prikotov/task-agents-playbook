<?php

declare(strict_types=1);

namespace Web\Module\Health\Form\Incident;

use Common\Module\Health\Application\Enum\IncidentSeverityEnum;
use Common\Module\Health\Application\Enum\IncidentStatusEnum;

final class FilterFormModel
{
    public function __construct(
        private ?IncidentStatusEnum $status = null,
        private ?IncidentSeverityEnum $severity = null,
        private bool $activeOnly = false,
    ) {
    }

    public function getStatus(): ?IncidentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(?IncidentStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getSeverity(): ?IncidentSeverityEnum
    {
        return $this->severity;
    }

    public function setSeverity(?IncidentSeverityEnum $severity): void
    {
        $this->severity = $severity;
    }

    public function isActiveOnly(): bool
    {
        return $this->activeOnly;
    }

    public function setActiveOnly(bool $activeOnly): void
    {
        $this->activeOnly = $activeOnly;
    }

    /**
     * @return array<string, string>
     */
    public function toQueryParams(?string $prefix = null): array
    {
        $params = [];

        if ($this->status !== null) {
            $key = $prefix === null ? 'status' : "{$prefix}[status]";
            $params[$key] = $this->status->value;
        }

        if ($this->severity !== null) {
            $key = $prefix === null ? 'severity' : "{$prefix}[severity]";
            $params[$key] = $this->severity->value;
        }

        if ($this->activeOnly) {
            $key = $prefix === null ? 'activeOnly' : "{$prefix}[activeOnly]";
            $params[$key] = '1';
        }

        return $params;
    }
}
