<?php

declare(strict_types=1);

namespace Common\Module\Health\Integration\Service\HealthChecker;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Common\Module\Source\Application\UseCase\Query\CheckDjvuHealth\CheckDjvuHealthQuery;
use Common\Module\Source\Application\UseCase\Query\CheckDjvuHealth\DjvuHealthDto;
use Override;

/**
 * Integration Service для проверки здоровья DjVu конвертера.
 *
 * Реализует CheckHealthServiceInterface для регистрации в HealthCheckerRegistry.
 * Вызывает Source Module через QueryBus согласно ADR-001.
 */
final readonly class DjvuHealthCheckerService implements CheckHealthServiceInterface
{
    private const string SERVICE_NAME = 'djvu';

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

        /** @var DjvuHealthDto $result */
        $result = $this->queryBus->query(new CheckDjvuHealthQuery());

        $responseTimeMs = round((microtime(true) - $startTime) * 1000.0, 2);

        if ($result->isHealthy) {
            return HealthCheckResultVo::createFromOperational(
                message: sprintf('DjVu converter is operational (version: %s)', $result->version ?? 'unknown'),
                responseTimeMs: $responseTimeMs,
            );
        }

        return HealthCheckResultVo::createFromOutage(
            message: sprintf('DjVu health check failed: %s', $result->errorMessage ?? 'unknown error'),
            responseTimeMs: $responseTimeMs,
        );
    }
}
