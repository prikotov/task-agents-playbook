<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Service\HealthChecker;

use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Common\Module\Health\Infrastructure\Component\DatabaseHealthCheck\DatabaseHealthCheckComponentInterface;
use Override;

/**
 * Сервис проверки здоровья PostgreSQL для регистрации в HealthCheckerRegistry.
 */
final readonly class DatabaseHealthCheckerService implements CheckHealthServiceInterface
{
    private const string SERVICE_NAME = 'postgresql';

    public function __construct(
        private DatabaseHealthCheckComponentInterface $component,
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
