<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Component\DatabaseHealthCheck;

use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Компонент для проверки здоровья PostgreSQL через Doctrine DBAL.
 */
final readonly class DatabaseHealthCheckComponent implements DatabaseHealthCheckComponentInterface
{
    private const string CHECK_QUERY = 'SELECT 1';

    /**
     * @param float|null $timeout Таймаут запроса в секундах (null = использовать default)
     */
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private ?float $timeout = null,
    ) {
    }

    #[Override]
    public function check(): HealthCheckResultVo
    {
        $startTime = microtime(true);

        $this->logger->info('DatabaseHealthCheckStart', [
            'query' => self::CHECK_QUERY,
        ]);

        try {
            $this->executeCheckQuery();

            $responseTimeMs = $this->calculateResponseTimeMs($startTime);
            $this->logSuccess($responseTimeMs);

            return HealthCheckResultVo::createFromOperational(
                message: 'PostgreSQL connection is healthy',
                responseTimeMs: $responseTimeMs,
            );
        } catch (Exception $e) {
            return $this->handleException($e, $startTime);
        } catch (Throwable $e) {
            return $this->handleException($e, $startTime);
        }
    }

    /**
     * Выполняет проверочный запрос к базе данных.
     *
     * @throws Exception При ошибке выполнения запроса
     */
    private function executeCheckQuery(): void
    {
        $result = $this->connection->executeQuery(self::CHECK_QUERY);
        $result->fetchOne();
        $result->free();
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
        $this->logger->info('DatabaseHealthCheckSuccess', [
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    /**
     * Обрабатывает исключение и возвращает результат с ошибкой.
     */
    private function handleException(Throwable $e, float $startTime): HealthCheckResultVo
    {
        $responseTimeMs = $this->calculateResponseTimeMs($startTime);

        $this->logger->error('DatabaseHealthCheckFailed', [
            'error' => $e->getMessage(),
            'response_time_ms' => $responseTimeMs,
        ]);

        return HealthCheckResultVo::createFromOutage(
            message: sprintf('PostgreSQL connection failed: %s', $e->getMessage()),
            responseTimeMs: $responseTimeMs,
        );
    }
}
