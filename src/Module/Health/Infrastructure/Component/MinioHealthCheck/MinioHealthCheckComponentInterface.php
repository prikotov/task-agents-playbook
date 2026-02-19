<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Component\MinioHealthCheck;

use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;

/**
 * Интерфейс компонента для проверки здоровья MinIO.
 */
interface MinioHealthCheckComponentInterface
{
    /**
     * Выполнить проверку соединения с MinIO.
     *
     * Использует S3 API для проверки доступности bucket.
     */
    public function check(): HealthCheckResultVo;
}
