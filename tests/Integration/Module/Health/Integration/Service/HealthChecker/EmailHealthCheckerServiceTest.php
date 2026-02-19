<?php

declare(strict_types=1);

namespace Common\Test\Integration\Module\Health\Integration\Service\HealthChecker;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Component\Test\IntegrationTestCase;
use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use Common\Module\Health\Integration\Service\HealthChecker\EmailHealthCheckerService;
use Common\Module\Notification\Application\UseCase\Query\CheckEmailHealth\CheckEmailHealthQuery;
use Common\Module\Notification\Application\UseCase\Query\CheckEmailHealth\EmailHealthDto;

/**
 * @group email
 */
final class EmailHealthCheckerServiceTest extends IntegrationTestCase
{
    private CheckHealthServiceInterface $service;
    private QueryBusComponentInterface $queryBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBus = self::getContainer()->get(QueryBusComponentInterface::class);
        $this->service = self::getContainer()->get(EmailHealthCheckerService::class);
    }

    public function testServiceImplementsCheckHealthServiceInterface(): void
    {
        self::assertInstanceOf(CheckHealthServiceInterface::class, $this->service);
    }

    public function testServiceReturnsCorrectName(): void
    {
        self::assertSame('email', $this->service->getName());
    }

    public function testServiceCheckReturnsResult(): void
    {
        $result = $this->service->check();

        self::assertNotNull($result);
        self::assertNotNull($result->status);
        self::assertNotNull($result->checkedAt);
        self::assertNotNull($result->responseTimeMs);
        self::assertGreaterThanOrEqual(0, $result->responseTimeMs);
    }

    public function testQueryBusReturnsEmailHealthDto(): void
    {
        $result = $this->queryBus->query(new CheckEmailHealthQuery());

        self::assertInstanceOf(EmailHealthDto::class, $result);
        self::assertIsBool($result->isHealthy);
    }
}
