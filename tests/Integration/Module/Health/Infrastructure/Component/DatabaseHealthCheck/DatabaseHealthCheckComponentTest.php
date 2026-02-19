<?php

declare(strict_types=1);

namespace Common\Test\Integration\Module\Health\Infrastructure\Component\DatabaseHealthCheck;

use Common\Component\Test\IntegrationTestCase;
use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use Common\Module\Health\Infrastructure\Component\DatabaseHealthCheck\DatabaseHealthCheckComponent;
use Common\Module\Health\Infrastructure\Component\DatabaseHealthCheck\DatabaseHealthCheckComponentInterface;
use Common\Module\Health\Infrastructure\Service\HealthChecker\DatabaseHealthCheckerService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final class DatabaseHealthCheckComponentTest extends IntegrationTestCase
{
    private DatabaseHealthCheckComponentInterface $component;
    private DatabaseHealthCheckerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Connection $connection */
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        /** @var LoggerInterface $logger */
        $logger = self::getContainer()->get('logger');

        $this->component = new DatabaseHealthCheckComponent($connection, $logger);
        $this->service = new DatabaseHealthCheckerService($this->component);
    }

    public function testComponentCheckReturnsOperational(): void
    {
        $result = $this->component->check();

        self::assertTrue($result->isOperational());
        self::assertSame(ServiceStatusEnum::operational, $result->status);
        self::assertStringContainsString('PostgreSQL', $result->message);
        self::assertNotNull($result->responseTimeMs);
        self::assertGreaterThan(0, $result->responseTimeMs);
    }

    public function testServiceGetNameReturnsPostgresql(): void
    {
        self::assertSame('postgresql', $this->service->getName());
    }

    public function testServiceCheckReturnsOperational(): void
    {
        $result = $this->service->check();

        self::assertTrue($result->isOperational());
        self::assertSame(ServiceStatusEnum::operational, $result->status);
    }

    public function testComponentHasCheckedAtTimestamp(): void
    {
        $result = $this->component->check();

        self::assertNotNull($result->checkedAt);
    }

    public function testServiceImplementsCheckHealthServiceInterface(): void
    {
        self::assertInstanceOf(
            \Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface::class,
            $this->service
        );
    }
}
