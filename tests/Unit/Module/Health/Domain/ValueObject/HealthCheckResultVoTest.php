<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Domain\ValueObject;

use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Domain\ValueObject\HealthCheckResultVo
 */
final class HealthCheckResultVoTest extends TestCase
{
    public function testOperationalFactoryMethodSetsDefaultValues(): void
    {
        $result = HealthCheckResultVo::createFromOperational();

        self::assertSame(ServiceStatusEnum::operational, $result->status);
        self::assertNull($result->message);
        self::assertNull($result->responseTimeMs);
        self::assertInstanceOf(\DateTimeImmutable::class, $result->checkedAt);
    }

    public function testOperationalFactoryMethodWithAllValues(): void
    {
        $result = HealthCheckResultVo::createFromOperational('All good', 50.0);

        self::assertSame(ServiceStatusEnum::operational, $result->status);
        self::assertSame('All good', $result->message);
        self::assertSame(50.0, $result->responseTimeMs);
    }

    public function testDegradedFactoryMethod(): void
    {
        $result = HealthCheckResultVo::createFromDegraded('Slow response', 500.0);

        self::assertSame(ServiceStatusEnum::degraded, $result->status);
        self::assertSame('Slow response', $result->message);
        self::assertSame(500.0, $result->responseTimeMs);
    }

    public function testDegradedFactoryMethodWithoutResponseTime(): void
    {
        $result = HealthCheckResultVo::createFromDegraded('Slow response');

        self::assertSame(ServiceStatusEnum::degraded, $result->status);
        self::assertSame('Slow response', $result->message);
        self::assertNull($result->responseTimeMs);
    }

    public function testOutageFactoryMethod(): void
    {
        $result = HealthCheckResultVo::createFromOutage('Connection refused');

        self::assertSame(ServiceStatusEnum::outage, $result->status);
        self::assertSame('Connection refused', $result->message);
    }

    public function testOutageFactoryMethodWithResponseTime(): void
    {
        $result = HealthCheckResultVo::createFromOutage('Connection refused', 0.0);

        self::assertSame(ServiceStatusEnum::outage, $result->status);
        self::assertSame('Connection refused', $result->message);
        self::assertSame(0.0, $result->responseTimeMs);
    }

    public function testMaintenanceFactoryMethod(): void
    {
        $result = HealthCheckResultVo::createFromMaintenance('Scheduled maintenance');

        self::assertSame(ServiceStatusEnum::maintenance, $result->status);
        self::assertSame('Scheduled maintenance', $result->message);
    }

    public function testMaintenanceFactoryMethodDefaultMessage(): void
    {
        $result = HealthCheckResultVo::createFromMaintenance();

        self::assertSame(ServiceStatusEnum::maintenance, $result->status);
        self::assertSame('Service is under maintenance', $result->message);
    }

    public function testIsOperationalReturnsTrueForOperationalStatus(): void
    {
        $result = HealthCheckResultVo::createFromOperational();

        self::assertTrue($result->isOperational());
    }

    /**
     * @dataProvider provideNonOperationalResults
     */
    public function testIsOperationalReturnsFalseForNonOperationalStatus(HealthCheckResultVo $result): void
    {
        self::assertFalse($result->isOperational());
    }

    /**
     * @return iterable<string, array{HealthCheckResultVo}>
     */
    public static function provideNonOperationalResults(): iterable
    {
        yield 'degraded' => [HealthCheckResultVo::createFromDegraded('test')];
        yield 'outage' => [HealthCheckResultVo::createFromOutage('test')];
        yield 'maintenance' => [HealthCheckResultVo::createFromMaintenance()];
    }

    /**
     * @dataProvider provideAttentionRequiredResults
     */
    public function testRequiresAttentionReturnsTrueForProblematicStatus(HealthCheckResultVo $result): void
    {
        self::assertTrue($result->requiresAttention());
    }

    /**
     * @return iterable<string, array{HealthCheckResultVo}>
     */
    public static function provideAttentionRequiredResults(): iterable
    {
        yield 'degraded' => [HealthCheckResultVo::createFromDegraded('test')];
        yield 'outage' => [HealthCheckResultVo::createFromOutage('test')];
    }

    /**
     * @dataProvider provideStableResults
     */
    public function testRequiresAttentionReturnsFalseForStableStatus(HealthCheckResultVo $result): void
    {
        self::assertFalse($result->requiresAttention());
    }

    /**
     * @return iterable<string, array{HealthCheckResultVo}>
     */
    public static function provideStableResults(): iterable
    {
        yield 'operational' => [HealthCheckResultVo::createFromOperational()];
        yield 'maintenance' => [HealthCheckResultVo::createFromMaintenance()];
    }

    public function testEqualsReturnsTrueForSameObject(): void
    {
        $result = HealthCheckResultVo::createFromOperational('OK', 100.0);

        self::assertTrue($result->equals($result));
    }

    public function testEqualsReturnsFalseForDifferentStatus(): void
    {
        $result1 = HealthCheckResultVo::createFromOperational();
        $result2 = HealthCheckResultVo::createFromDegraded('test');

        self::assertFalse($result1->equals($result2));
    }

    public function testEqualsReturnsFalseForDifferentMessage(): void
    {
        $result1 = HealthCheckResultVo::createFromOperational('OK');
        $result2 = HealthCheckResultVo::createFromOperational('Different');

        self::assertFalse($result1->equals($result2));
    }

    public function testEqualsReturnsFalseForDifferentResponseTime(): void
    {
        $result1 = HealthCheckResultVo::createFromOperational('OK', 100.0);
        $result2 = HealthCheckResultVo::createFromOperational('OK', 200.0);

        self::assertFalse($result1->equals($result2));
    }

    public function testEqualsReturnsFalseForDifferentCheckedAt(): void
    {
        $result1 = HealthCheckResultVo::createFromOperational('OK');
        usleep(1000); // 1ms delay
        $result2 = HealthCheckResultVo::createFromOperational('OK');

        // checkedAt differs because of automatic timestamp
        self::assertFalse($result1->equals($result2));
    }
}
