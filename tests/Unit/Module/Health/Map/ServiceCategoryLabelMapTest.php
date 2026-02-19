<?php

declare(strict_types=1);

namespace Tests\Unit\Module\Health\Map;

use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Web\Module\Health\Map\ServiceCategoryLabelMap;

final class ServiceCategoryLabelMapTest extends TestCase
{
    private ServiceCategoryLabelMap $map;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(fn (string $id) => match ($id) {
                'health.category.infrastructure' => 'Infrastructure',
                'health.category.llm' => 'LLM Providers',
                'health.category.external_api' => 'External APIs',
                'health.category.cli_tool' => 'CLI Tools',
                default => $id,
            });

        $this->map = new ServiceCategoryLabelMap($translator);
    }

    public function testGetAssociationByValueInfrastructure(): void
    {
        self::assertSame('Infrastructure', $this->map->getAssociationByValue(ServiceCategoryEnum::infrastructure));
    }

    public function testGetAssociationByValueLlm(): void
    {
        self::assertSame('LLM Providers', $this->map->getAssociationByValue(ServiceCategoryEnum::llm));
    }

    public function testGetAssociationByValueExternalApi(): void
    {
        self::assertSame('External APIs', $this->map->getAssociationByValue(ServiceCategoryEnum::externalApi));
    }

    public function testGetAssociationByValueCliTool(): void
    {
        self::assertSame('CLI Tools', $this->map->getAssociationByValue(ServiceCategoryEnum::cliTool));
    }

    public function testGetAssociationByValueAllCategoriesCovered(): void
    {
        foreach (ServiceCategoryEnum::cases() as $category) {
            $result = $this->map->getAssociationByValue($category);
            self::assertNotEmpty($result);
        }
    }
}
