<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Domain\Enum;

use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Domain\Enum\ServiceStatusEnum
 */
final class ServiceStatusEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = ServiceStatusEnum::cases();

        self::assertCount(4, $cases);
        self::assertContains(ServiceStatusEnum::operational, $cases);
        self::assertContains(ServiceStatusEnum::degraded, $cases);
        self::assertContains(ServiceStatusEnum::outage, $cases);
        self::assertContains(ServiceStatusEnum::maintenance, $cases);
    }

    public function testValuesAreStrings(): void
    {
        self::assertSame('operational', ServiceStatusEnum::operational->value);
        self::assertSame('degraded', ServiceStatusEnum::degraded->value);
        self::assertSame('outage', ServiceStatusEnum::outage->value);
        self::assertSame('maintenance', ServiceStatusEnum::maintenance->value);
    }

    public function testCanCreateFromValue(): void
    {
        self::assertSame(ServiceStatusEnum::operational, ServiceStatusEnum::from('operational'));
        self::assertSame(ServiceStatusEnum::degraded, ServiceStatusEnum::from('degraded'));
        self::assertSame(ServiceStatusEnum::outage, ServiceStatusEnum::from('outage'));
        self::assertSame(ServiceStatusEnum::maintenance, ServiceStatusEnum::from('maintenance'));
    }
}
