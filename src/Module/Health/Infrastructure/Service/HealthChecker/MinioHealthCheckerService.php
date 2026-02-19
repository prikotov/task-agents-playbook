<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Service\HealthChecker;

use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Common\Module\Health\Infrastructure\Component\MinioHealthCheck\MinioHealthCheckComponentInterface;
use Override;

/**
 * Сервис проверки здоровья MinIO для регистрации в HealthCheckerRegistry.
 */
final readonly class MinioHealthCheckerService implements CheckHealthServiceInterface
{
    private const string SERVICE_NAME = 'minio';

    public function __construct(
        private MinioHealthCheckComponentInterface $component,
    ) {
    }

    #[Override]
    public function getName(): string
    {
        return self::SERVICE_NAME;
    }

    #[Override]
    public function check(): HealthCheckResultVo
    {
        return $this->component->check();
    }
}
