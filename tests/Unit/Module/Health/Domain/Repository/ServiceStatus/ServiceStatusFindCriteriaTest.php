<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Domain\Repository\ServiceStatus;

use Common\Module\Health\Domain\Enum\ServiceCategoryEnum;
use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use Common\Module\Health\Domain\Repository\ServiceStatus\Criteria\ServiceStatusFindCriteria;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Domain\Repository\ServiceStatus\Criteria\ServiceStatusFindCriteria
 */
final class ServiceStatusFindCriteriaTest extends TestCase
{
    public function testConstructorSetsDefaultValues(): void
    {
        $criteria = new ServiceStatusFindCriteria();

        self::assertNull($criteria->getStatus());
        self::assertNull($criteria->getCategory());
        self::assertNull($criteria->getName());
    }

    public function testConstructorSetsAllValues(): void
    {
        $criteria = new ServiceStatusFindCriteria(
            status: ServiceStatusEnum::operational,
            category: ServiceCategoryEnum::infrastructure,
            name: 'postgresql',
        );

        self::assertSame(ServiceStatusEnum::operational, $criteria->getStatus());
        self::assertSame(ServiceCategoryEnum::infrastructure, $criteria->getCategory());
        self::assertSame('postgresql', $criteria->getName());
    }

    public function testSettersWork(): void
    {
        $criteria = new ServiceStatusFindCriteria();

        $criteria->setStatus(ServiceStatusEnum::outage);
        $criteria->setCategory(ServiceCategoryEnum::llm);
        $criteria->setName('openai');

        self::assertSame(ServiceStatusEnum::outage, $criteria->getStatus());
        self::assertSame(ServiceCategoryEnum::llm, $criteria->getCategory());
        self::assertSame('openai', $criteria->getName());
    }

    public function testSettersReturnSelf(): void
    {
        $criteria = new ServiceStatusFindCriteria();

        self::assertSame($criteria, $criteria->setStatus(ServiceStatusEnum::degraded));
        self::assertSame($criteria, $criteria->setCategory(ServiceCategoryEnum::cliTool));
        self::assertSame($criteria, $criteria->setName('yt-dlp'));
    }

    public function testImplementsCriteriaInterface(): void
    {
        $criteria = new ServiceStatusFindCriteria();

        self::assertInstanceOf(
            \Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusCriteriaInterface::class,
            $criteria,
        );
    }

    public function testImplementsSortableCriteriaInterface(): void
    {
        $criteria = new ServiceStatusFindCriteria();

        self::assertInstanceOf(
            \Common\Component\Repository\SortableCriteriaInterface::class,
            $criteria,
        );
    }

    public function testImplementsCriteriaWithLimitInterface(): void
    {
        $criteria = new ServiceStatusFindCriteria();

        self::assertInstanceOf(
            \Common\Component\Repository\CriteriaWithLimitInterface::class,
            $criteria,
        );
    }

    public function testImplementsCriteriaWithOffsetInterface(): void
    {
        $criteria = new ServiceStatusFindCriteria();

        self::assertInstanceOf(
            \Common\Component\Repository\CriteriaWithOffsetInterface::class,
            $criteria,
        );
    }
}
