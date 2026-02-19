<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Application\Service\HealthChecker;

use Common\Module\Health\Application\Service\HealthChecker\HealthCheckerRegistryService;
use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Application\Service\HealthChecker\HealthCheckerRegistryService
 */
final class HealthCheckerRegistryServiceTest extends TestCase
{
    public function testRegisterAddsChecker(): void
    {
        $registry = new HealthCheckerRegistryService();
        $checker = $this->createMock(CheckHealthServiceInterface::class);
        $checker->method('getName')->willReturn('postgresql');

        $result = $registry->register($checker);

        self::assertSame($registry, $result);
        self::assertTrue($registry->hasChecker('postgresql'));
        self::assertSame($checker, $registry->getChecker('postgresql'));
    }

    public function testRegisterThrowsExceptionForDuplicateChecker(): void
    {
        $registry = new HealthCheckerRegistryService();
        $checker1 = $this->createMock(CheckHealthServiceInterface::class);
        $checker1->method('getName')->willReturn('postgresql');
        $checker2 = $this->createMock(CheckHealthServiceInterface::class);
        $checker2->method('getName')->willReturn('postgresql');

        $registry->register($checker1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Health checker "postgresql" is already registered.');

        $registry->register($checker2);
    }

    public function testGetCheckerReturnsNullForUnknownService(): void
    {
        $registry = new HealthCheckerRegistryService();

        self::assertNull($registry->getChecker('unknown'));
    }

    public function testHasCheckerReturnsFalseForUnknownService(): void
    {
        $registry = new HealthCheckerRegistryService();

        self::assertFalse($registry->hasChecker('unknown'));
    }

    public function testGetAllCheckersReturnsAllRegistered(): void
    {
        $registry = new HealthCheckerRegistryService();
        $checker1 = $this->createMock(CheckHealthServiceInterface::class);
        $checker1->method('getName')->willReturn('postgresql');
        $checker2 = $this->createMock(CheckHealthServiceInterface::class);
        $checker2->method('getName')->willReturn('rabbitmq');

        $registry->register($checker1);
        $registry->register($checker2);

        $checkers = $registry->getAllCheckers();

        self::assertCount(2, $checkers);
        self::assertArrayHasKey('postgresql', $checkers);
        self::assertArrayHasKey('rabbitmq', $checkers);
    }

    public function testGetRegisteredServiceNamesReturnsNames(): void
    {
        $registry = new HealthCheckerRegistryService();
        $checker1 = $this->createMock(CheckHealthServiceInterface::class);
        $checker1->method('getName')->willReturn('postgresql');
        $checker2 = $this->createMock(CheckHealthServiceInterface::class);
        $checker2->method('getName')->willReturn('rabbitmq');

        $registry->register($checker1);
        $registry->register($checker2);

        $names = $registry->getRegisteredServiceNames();

        self::assertSame(['postgresql', 'rabbitmq'], $names);
    }

    public function testGetRegisteredServiceNamesReturnsEmptyArrayWhenNoCheckers(): void
    {
        $registry = new HealthCheckerRegistryService();

        self::assertSame([], $registry->getRegisteredServiceNames());
    }

    public function testUnregisterRemovesChecker(): void
    {
        $registry = new HealthCheckerRegistryService();
        $checker = $this->createMock(CheckHealthServiceInterface::class);
        $checker->method('getName')->willReturn('postgresql');

        $registry->register($checker);
        self::assertTrue($registry->hasChecker('postgresql'));

        $result = $registry->unregister('postgresql');

        self::assertSame($registry, $result);
        self::assertFalse($registry->hasChecker('postgresql'));
    }

    public function testUnregisterSilentlyIgnoresUnknownService(): void
    {
        $registry = new HealthCheckerRegistryService();

        // Should not throw
        $result = $registry->unregister('unknown');

        self::assertSame($registry, $result);
    }

    public function testClearRemovesAllCheckers(): void
    {
        $registry = new HealthCheckerRegistryService();
        $checker1 = $this->createMock(CheckHealthServiceInterface::class);
        $checker1->method('getName')->willReturn('postgresql');
        $checker2 = $this->createMock(CheckHealthServiceInterface::class);
        $checker2->method('getName')->willReturn('rabbitmq');

        $registry->register($checker1);
        $registry->register($checker2);
        self::assertCount(2, $registry->getAllCheckers());

        $result = $registry->clear();

        self::assertSame($registry, $result);
        self::assertSame([], $registry->getAllCheckers());
        self::assertSame([], $registry->getRegisteredServiceNames());
    }
}
