<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Domain\Enum;

use Common\Module\Health\Domain\Enum\ServiceCategoryEnum;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Domain\Enum\ServiceCategoryEnum
 */
final class ServiceCategoryEnumTest extends TestCase
{
    public function testAllCasesExist(): void
    {
        $cases = ServiceCategoryEnum::cases();

        self::assertCount(4, $cases);
        self::assertContains(ServiceCategoryEnum::infrastructure, $cases);
        self::assertContains(ServiceCategoryEnum::llm, $cases);
        self::assertContains(ServiceCategoryEnum::externalApi, $cases);
        self::assertContains(ServiceCategoryEnum::cliTool, $cases);
    }

    public function testValuesAreStrings(): void
    {
        self::assertSame('infrastructure', ServiceCategoryEnum::infrastructure->value);
        self::assertSame('llm', ServiceCategoryEnum::llm->value);
        self::assertSame('external_api', ServiceCategoryEnum::externalApi->value);
        self::assertSame('cli_tool', ServiceCategoryEnum::cliTool->value);
    }

    public function testCanCreateFromValue(): void
    {
        self::assertSame(ServiceCategoryEnum::infrastructure, ServiceCategoryEnum::from('infrastructure'));
        self::assertSame(ServiceCategoryEnum::llm, ServiceCategoryEnum::from('llm'));
        self::assertSame(ServiceCategoryEnum::externalApi, ServiceCategoryEnum::from('external_api'));
        self::assertSame(ServiceCategoryEnum::cliTool, ServiceCategoryEnum::from('cli_tool'));
    }
}
