<?php

declare(strict_types=1);

namespace Web\Module\Health\List;

use Common\Module\Health\Application\Enum\ServiceCategoryEnum;

/**
 * List-класс для категорий сервисов в фильтре дашборда.
 */
final class ServiceCategoryFilterList
{
    /**
     * @return list<ServiceCategoryEnum>
     */
    public function all(): array
    {
        return [
            ServiceCategoryEnum::infrastructure,
            ServiceCategoryEnum::llm,
            ServiceCategoryEnum::externalApi,
            ServiceCategoryEnum::cliTool,
        ];
    }
}
