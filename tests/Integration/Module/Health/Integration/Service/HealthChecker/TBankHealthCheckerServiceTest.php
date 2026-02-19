<?php

declare(strict_types=1);

namespace Common\Test\Integration\Module\Health\Integration\Service\HealthChecker;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Component\Test\IntegrationTestCase;
use Common\Module\Billing\Application\UseCase\Query\CheckTBankHealth\CheckTBankHealthQuery;
use Common\Module\Billing\Application\UseCase\Query\CheckTBankHealth\TBankHealthDto;
use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use Common\Module\Health\Integration\Service\HealthChecker\TBankHealthCheckerService;

/**
 * @group tbank
 */
final class TBankHealthCheckerServiceTest extends IntegrationTestCase
{
    private CheckHealthServiceInterface $service;
    private QueryBusComponentInterface $queryBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBus = self::getContainer()->get(QueryBusComponentInterface::class);
        $this->service = self::getContainer()->get(TBankHealthCheckerService::class);
    }

    public function testServiceImplementsCheckHealthServiceInterface(): void
    {
        self::assertInstanceOf(CheckHealthServiceInterface::class, $this->service);
    }

    public function testServiceReturnsCorrectName(): void
    {
        self::assertSame('tbank', $this->service->getName());
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

    public function testQueryBusReturnsTBankHealthDto(): void
    {
        $result = $this->queryBus->query(new CheckTBankHealthQuery());

        self::assertInstanceOf(TBankHealthDto::class, $result);
        self::assertIsBool($result->isHealthy);
    }
}
