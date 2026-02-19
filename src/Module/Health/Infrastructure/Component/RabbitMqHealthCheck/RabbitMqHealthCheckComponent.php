<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Component\RabbitMqHealthCheck;

use AMQPConnection;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Override;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Компонент для проверки здоровья RabbitMQ через AMQP extension.
 */
final readonly class RabbitMqHealthCheckComponent implements RabbitMqHealthCheckComponentInterface
{
    /**
     * @param AMQPConnection $connection AMQP соединение с RabbitMQ
     * @param LoggerInterface $logger Логгер
     * @param float|null $timeout Таймаут подключения в секундах (null = использовать default)
     */
    public function __construct(
        private AMQPConnection $connection,
        private LoggerInterface $logger,
        private ?float $timeout = null,
    ) {
    }

    #[Override]
    public function check(): HealthCheckResultVo
    {
        $startTime = microtime(true);

        $this->logger->info('RabbitMqHealthCheckStart', [
            'host' => $this->connection->getHost(),
            'port' => $this->connection->getPort(),
            'vhost' => $this->connection->getVhost(),
        ]);

        try {
            $this->performHealthCheck();

            $responseTimeMs = $this->calculateResponseTimeMs($startTime);
            $this->logSuccess($responseTimeMs);

            return HealthCheckResultVo::createFromOperational(
                message: 'RabbitMQ connection is healthy',
                responseTimeMs: $responseTimeMs,
            );
        } catch (Throwable $e) {
            return $this->handleException($e, $startTime);
        }
    }

    /**
     * Выполняет проверку здоровья RabbitMQ.
     *
     * @throws Throwable При ошибке подключения
     */
    private function performHealthCheck(): void
    {
        // Если соединение уже установлено, проверяем его
        if ($this->connection->isConnected()) {
            return;
        }

        // Устанавливаем таймаут если передан
        if ($this->timeout !== null) {
            $this->connection->setReadTimeout($this->timeout);
            $this->connection->setWriteTimeout($this->timeout);
        }

        // Пытаемся подключиться
        $this->connection->connect();

        // Проверяем что подключение действительно установлено
        if (!$this->connection->isConnected()) {
            throw new RuntimeException('Failed to establish RabbitMQ connection');
        }

        // Закрываем соединение после проверки (для health check не нужно держать соединение)
        $this->connection->disconnect();
    }

    /**
     * Вычисляет время ответа в миллисекундах.
     */
    private function calculateResponseTimeMs(float $startTime): float
    {
        return round((microtime(true) - $startTime) * 1000.0, 2);
    }

    /**
     * Логирует успешную проверку.
     */
    private function logSuccess(float $responseTimeMs): void
    {
        $this->logger->info('RabbitMqHealthCheckSuccess', [
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    /**
     * Обрабатывает исключение и возвращает результат с ошибкой.
     */
    private function handleException(Throwable $e, float $startTime): HealthCheckResultVo
    {
        $responseTimeMs = $this->calculateResponseTimeMs($startTime);

        $this->logger->error('RabbitMqHealthCheckFailed', [
            'error' => $e->getMessage(),
            'response_time_ms' => $responseTimeMs,
        ]);

        return HealthCheckResultVo::createFromOutage(
            message: sprintf('RabbitMQ connection failed: %s', $e->getMessage()),
            responseTimeMs: $responseTimeMs,
        );
    }
}
