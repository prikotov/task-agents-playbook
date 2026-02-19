<?php

declare(strict_types=1);

namespace Web\Module\Health\Map;

use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use OutOfBoundsException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Map-класс для текстового представления категорий сервисов.
 */
final class ServiceCategoryLabelMap
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array<string, string>
     */
    private function categoryLabels(): array
    {
        return [
            ServiceCategoryEnum::infrastructure->value => $this->translator->trans('health.category.infrastructure', domain: 'messages'),
            ServiceCategoryEnum::llm->value => $this->translator->trans('health.category.llm', domain: 'messages'),
            ServiceCategoryEnum::externalApi->value => $this->translator->trans('health.category.external_api', domain: 'messages'),
            ServiceCategoryEnum::cliTool->value => $this->translator->trans('health.category.cli_tool', domain: 'messages'),
        ];
    }

    /**
     * @throws OutOfBoundsException
     */
    public function getAssociationByValue(ServiceCategoryEnum $category): string
    {
        return $this->categoryLabels()[$category->value]
            ?? throw new OutOfBoundsException("No bounded association for value '{$category->value}'");
    }
}
