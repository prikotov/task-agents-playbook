<?php

declare(strict_types=1);

namespace Tests\Unit\Module\Health\List;

use Common\Module\Health\Application\Enum\ServiceStatusEnum;
use PHPUnit\Framework\TestCase;
use Web\Module\Health\List\ServiceStatusFilterList;

final class ServiceStatusFilterListTest extends TestCase
{
    private ServiceStatusFilterList $list;

    protected function setUp(): void
    {
        $this->list = new ServiceStatusFilterList();
    }

    public function testAllReturnsListOfStatusEnums(): void
    {
        $result = $this->list->all();

        self::assertCount(4, $result);
        self::assertContainsOnlyInstancesOf(ServiceStatusEnum::class, $result);
    }

    public function testAllReturnsOperationalFirst(): void
    {
        $result = $this->list->all();

        self::assertSame(ServiceStatusEnum::operational, $result[0]);
    }

    public function testAllReturnsDegradedSecond(): void
    {
        $result = $this->list->all();

        self::assertSame(ServiceStatusEnum::degraded, $result[1]);
    }

    public function testAllReturnsOutageThird(): void
    {
        $result = $this->list->all();

        self::assertSame(ServiceStatusEnum::outage, $result[2]);
    }

    public function testAllReturnsMaintenanceFourth(): void
    {
        $result = $this->list->all();

        self::assertSame(ServiceStatusEnum::maintenance, $result[3]);
    }

    public function testAllReturnsListWithoutKeys(): void
    {
        $result = $this->list->all();

        self::assertSame(array_values($result), $result);
    }
}
