<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Component\MinioHealthCheck;

use Aws\S3\S3Client;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Компонент для проверки здоровья MinIO через S3 API.
 */
final readonly class MinioHealthCheckComponent implements MinioHealthCheckComponentInterface
{
    /**
     * @param S3Client $client S3 клиент для подключения к MinIO
     * @param LoggerInterface $logger Логгер
     * @param string $bucket Имя bucket для проверки
     */
    public function __construct(
        private S3Client $client,
        private LoggerInterface $logger,
        private string $bucket,
    ) {
    }

    #[Override]
    public function check(): HealthCheckResultVo
    {
        $startTime = microtime(true);

        $this->logger->info('MinioHealthCheckStart', [
            'bucket' => $this->bucket,
        ]);

        try {
            $this->executeHeadBucket();

            $responseTimeMs = $this->calculateResponseTimeMs($startTime);
            $this->logSuccess($responseTimeMs);

            return HealthCheckResultVo::createFromOperational(
                message: 'MinIO connection is healthy',
                responseTimeMs: $responseTimeMs,
            );
        } catch (Throwable $e) {
            return $this->handleException($e, $startTime);
        }
    }

    /**
     * Выполняет HeadBucket запрос для проверки доступности bucket.
     *
     * @throws Throwable При ошибке выполнения запроса
     */
    private function executeHeadBucket(): void
    {
        $this->client->headBucket([
            'Bucket' => $this->bucket,
        ]);
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
        $this->logger->info('MinioHealthCheckSuccess', [
            'bucket' => $this->bucket,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    /**
     * Обрабатывает исключение и возвращает результат с ошибкой.
     */
    private function handleException(Throwable $e, float $startTime): HealthCheckResultVo
    {
        $responseTimeMs = $this->calculateResponseTimeMs($startTime);

        $this->logger->error('MinioHealthCheckFailed', [
            'bucket' => $this->bucket,
            'error' => $e->getMessage(),
            'response_time_ms' => $responseTimeMs,
        ]);

        return HealthCheckResultVo::createFromOutage(
            message: sprintf('MinIO connection failed: %s', $e->getMessage()),
            responseTimeMs: $responseTimeMs,
        );
    }
}
