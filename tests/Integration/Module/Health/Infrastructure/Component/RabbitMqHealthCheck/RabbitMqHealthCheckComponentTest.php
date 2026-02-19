<?php

declare(strict_types=1);

namespace Common\Test\Integration\Module\Health\Infrastructure\Component\RabbitMqHealthCheck;

use AMQPConnection;
use Common\Component\Test\IntegrationTestCase;
use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use Common\Module\Health\Infrastructure\Component\RabbitMqHealthCheck\RabbitMqHealthCheckComponent;
use Common\Module\Health\Infrastructure\Component\RabbitMqHealthCheck\RabbitMqHealthCheckComponentInterface;
use Common\Module\Health\Infrastructure\Service\HealthChecker\RabbitMqHealthCheckerService;
use Psr\Log\LoggerInterface;

final class RabbitMqHealthCheckComponentTest extends IntegrationTestCase
{
    private RabbitMqHealthCheckComponentInterface $component;
    private RabbitMqHealthCheckerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var LoggerInterface $logger */
        $logger = self::getContainer()->get('logger');

        // Создаём AMQPConnection из переменных окружения
        $connection = new AMQPConnection([
            'host' => $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
            'port' => (int) ($_ENV['RABBITMQ_PORT'] ?? 5672),
            'vhost' => $_ENV['RABBITMQ_VHOST'] ?? 'task',
            'login' => $_ENV['RABBITMQ_USER'] ?? 'task',
            'password' => $_ENV['RABBITMQ_PASSWORD'] ?? 'task',
        ]);

        $this->component = new RabbitMqHealthCheckComponent($connection, $logger);
        $this->service = new RabbitMqHealthCheckerService($this->component);
    }

    public function testComponentCheckReturnsOperational(): void
    {
        $result = $this->component->check();

        self::assertTrue($result->isOperational());
        self::assertSame(ServiceStatusEnum::operational, $result->status);
        self::assertStringContainsString('RabbitMQ', $result->message);
        self::assertNotNull($result->responseTimeMs);
        self::assertGreaterThan(0, $result->responseTimeMs);
    }

    public function testServiceGetNameReturnsRabbitmq(): void
    {
        self::assertSame('rabbitmq', $this->service->getName());
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
