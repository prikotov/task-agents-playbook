<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Application\UseCase\Query\GetServiceStatus;

use Common\Application\Query\QueryInterface;
use Common\Module\Health\Application\UseCase\Query\GetServiceStatus\GetServiceStatusQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Application\UseCase\Query\GetServiceStatus\GetServiceStatusQuery
 */
final class GetServiceStatusQueryTest extends TestCase
{
    public function testImplementsQueryInterface(): void
    {
        $query = new GetServiceStatusQuery(serviceName: 'postgresql');

        self::assertInstanceOf(QueryInterface::class, $query);
    }

    public function testConstructorSetsServiceName(): void
    {
        $query = new GetServiceStatusQuery(serviceName: 'postgresql');

        self::assertSame('postgresql', $query->serviceName);
    }
}
