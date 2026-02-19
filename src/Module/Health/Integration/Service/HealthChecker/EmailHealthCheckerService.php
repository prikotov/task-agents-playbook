<?php

declare(strict_types=1);

namespace Common\Module\Health\Integration\Service\HealthChecker;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Common\Module\Notification\Application\UseCase\Query\CheckEmailHealth\CheckEmailHealthQuery;
use Common\Module\Notification\Application\UseCase\Query\CheckEmailHealth\EmailHealthDto;
use Override;

/**
 * Integration Service для проверки здоровья Email/SMTP сервиса.
 *
 * Реализует CheckHealthServiceInterface для регистрации в HealthCheckerRegistry.
 * Вызывает Notification Module через QueryBus согласно ADR-002.
 */
final readonly class EmailHealthCheckerService implements CheckHealthServiceInterface
{
    private const string SERVICE_NAME = 'email';

    public function __construct(
        private QueryBusComponentInterface $queryBus,
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
        $startTime = microtime(true);

        /** @var EmailHealthDto $result */
        $result = $this->queryBus->query(new CheckEmailHealthQuery());

        $responseTimeMs = round((microtime(true) - $startTime) * 1000.0, 2);

        if ($result->isHealthy) {
            return HealthCheckResultVo::createFromOperational(
                message: 'Email service is operational',
                responseTimeMs: $responseTimeMs,
            );
        }

        return HealthCheckResultVo::createFromOutage(
            message: sprintf('Email service health check failed: %s', $result->errorMessage ?? 'unknown error'),
            responseTimeMs: $responseTimeMs,
        );
    }
}
