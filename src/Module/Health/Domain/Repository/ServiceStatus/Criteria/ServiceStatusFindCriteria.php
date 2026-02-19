<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Repository\ServiceStatus\Criteria;

use Common\Component\Repository\CriteriaWithLimitInterface;
use Common\Component\Repository\CriteriaWithOffsetInterface;
use Common\Component\Repository\SortableCriteriaInterface;
use Common\Component\Repository\Trait\CriteriaWithLimitTrait;
use Common\Component\Repository\Trait\CriteriaWithOffsetTrait;
use Common\Component\Repository\Trait\SortableCriteriaTrait;
use Common\Module\Health\Domain\Enum\ServiceCategoryEnum;
use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusCriteriaInterface;

/**
 * Критерии для поиска статусов сервисов.
 */
final class ServiceStatusFindCriteria implements
    ServiceStatusCriteriaInterface,
    SortableCriteriaInterface,
    CriteriaWithLimitInterface,
    CriteriaWithOffsetInterface
{
    use SortableCriteriaTrait;
    use CriteriaWithLimitTrait;
    use CriteriaWithOffsetTrait;

    public function __construct(
        private ?ServiceStatusEnum $status = null,
        private ?ServiceCategoryEnum $category = null,
        private ?string $name = null,
    ) {
    }

    public function getStatus(): ?ServiceStatusEnum
    {
        return $this->status;
    }

    public function setStatus(?ServiceStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCategory(): ?ServiceCategoryEnum
    {
        return $this->category;
    }

    public function setCategory(?ServiceCategoryEnum $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }
}
