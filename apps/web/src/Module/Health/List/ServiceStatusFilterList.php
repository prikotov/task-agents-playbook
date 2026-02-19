<?php

declare(strict_types=1);

namespace Web\Module\Health\List;

use Common\Module\Health\Application\Enum\ServiceStatusEnum;

/**
 * List-класс для статусов сервисов в фильтре дашборда.
 */
final class ServiceStatusFilterList
{
    /**
     * @return list<ServiceStatusEnum>
     */
    public function all(): array
    {
        return [
            ServiceStatusEnum::operational,
            ServiceStatusEnum::degraded,
            ServiceStatusEnum::outage,
            ServiceStatusEnum::maintenance,
        ];
    }
}
