<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Infrastructure\Service\HealthChecker;

use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Common\Module\Health\Infrastructure\Component\DatabaseHealthCheck\DatabaseHealthCheckComponentInterface;
use Common\Module\Health\Infrastructure\Service\HealthChecker\DatabaseHealthCheckerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DatabaseHealthCheckerServiceTest extends TestCase
{
    private DatabaseHealthCheckComponentInterface&MockObject $component;
    private DatabaseHealthCheckerService $service;

    protected function setUp(): void
    {
        $this->component = $this->createMock(DatabaseHealthCheckComponentInterface::class);
        $this->service = new DatabaseHealthCheckerService($this->component);
    }

    public function testGetNameReturnsPostgresql(): void
    {
        self::assertSame('postgresql', $this->service->getName());
    }

    public function testCheckDelegatesToComponent(): void
    {
        $expectedResult = HealthCheckResultVo::createFromOperational(
            message: 'PostgreSQL connection is healthy',
            responseTimeMs: 1.5,
        );

        $this->component
            ->expects(self::once())
            ->method('check')
            ->willReturn($expectedResult);

        $result = $this->service->check();

        self::assertSame($expectedResult, $result);
        self::assertTrue($result->isOperational());
    }

    public function testCheckReturnsOutageOnFailure(): void
    {
        $expectedResult = HealthCheckResultVo::createFromOutage(
            message: 'PostgreSQL connection failed: Connection refused',
            responseTimeMs: 1000.0,
        );

        $this->component
            ->expects(self::once())
            ->method('check')
            ->willReturn($expectedResult);

        $result = $this->service->check();

        self::assertFalse($result->isOperational());
        self::assertTrue($result->requiresAttention());
        self::assertSame(ServiceStatusEnum::outage, $result->status);
    }

    public function testImplementsCheckHealthServiceInterface(): void
    {
        self::assertInstanceOf(
            \Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface::class,
            $this->service
        );
    }
}
