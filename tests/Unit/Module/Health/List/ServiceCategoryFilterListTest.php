<?php

declare(strict_types=1);

namespace Tests\Unit\Module\Health\List;

use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use PHPUnit\Framework\TestCase;
use Web\Module\Health\List\ServiceCategoryFilterList;

final class ServiceCategoryFilterListTest extends TestCase
{
    private ServiceCategoryFilterList $list;

    protected function setUp(): void
    {
        $this->list = new ServiceCategoryFilterList();
    }

    public function testAllReturnsListOfCategoryEnums(): void
    {
        $result = $this->list->all();

        self::assertCount(4, $result);
        self::assertContainsOnlyInstancesOf(ServiceCategoryEnum::class, $result);
    }

    public function testAllReturnsInfrastructureFirst(): void
    {
        $result = $this->list->all();

        self::assertSame(ServiceCategoryEnum::infrastructure, $result[0]);
    }

    public function testAllReturnsLlmSecond(): void
    {
        $result = $this->list->all();

        self::assertSame(ServiceCategoryEnum::llm, $result[1]);
    }

    public function testAllReturnsExternalApiThird(): void
    {
        $result = $this->list->all();

        self::assertSame(ServiceCategoryEnum::externalApi, $result[2]);
    }

    public function testAllReturnsCliToolFourth(): void
    {
        $result = $this->list->all();

        self::assertSame(ServiceCategoryEnum::cliTool, $result[3]);
    }

    public function testAllReturnsListWithoutKeys(): void
    {
        $result = $this->list->all();

        self::assertSame(array_values($result), $result);
    }
}
