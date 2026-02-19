<?php

declare(strict_types=1);

namespace Web\Module\Health\Form\Dashboard;

use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use Common\Module\Health\Application\Enum\ServiceStatusEnum;

final class FilterFormModel
{
    public function __construct(
        private ?ServiceCategoryEnum $category = null,
        private ?ServiceStatusEnum $status = null,
    ) {
    }

    public function getCategory(): ?ServiceCategoryEnum
    {
        return $this->category;
    }

    public function setCategory(?ServiceCategoryEnum $category): void
    {
        $this->category = $category;
    }

    public function getStatus(): ?ServiceStatusEnum
    {
        return $this->status;
    }

    public function setStatus(?ServiceStatusEnum $status): void
    {
        $this->status = $status;
    }

    /**
     * @return array<string, string>
     */
    public function toQueryParams(?string $prefix = null): array
    {
        $params = [];

        if ($this->category !== null) {
            $key = $prefix === null ? 'category' : "{$prefix}[category]";
            $params[$key] = $this->category->value;
        }

        if ($this->status !== null) {
            $key = $prefix === null ? 'status' : "{$prefix}[status]";
            $params[$key] = $this->status->value;
        }

        return $params;
    }
}
