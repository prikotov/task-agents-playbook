<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Application\UseCase\Query\GetSystemHealth;

use Common\Application\Query\QueryInterface;
use Common\Module\Health\Application\UseCase\Query\GetSystemHealth\GetSystemHealthQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Application\UseCase\Query\GetSystemHealth\GetSystemHealthQuery
 */
final class GetSystemHealthQueryTest extends TestCase
{
    public function testImplementsQueryInterface(): void
    {
        $query = new GetSystemHealthQuery();

        self::assertInstanceOf(QueryInterface::class, $query);
    }

    public function testDefaultValues(): void
    {
        $query = new GetSystemHealthQuery();

        self::assertNull($query->category);
    }

    public function testConstructorSetsCategory(): void
    {
        $query = new GetSystemHealthQuery(category: 'infrastructure');

        self::assertSame('infrastructure', $query->category);
    }
}
