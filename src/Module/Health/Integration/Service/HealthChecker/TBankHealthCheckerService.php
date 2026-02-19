<?php

declare(strict_types=1);

namespace Common\Module\Health\Integration\Service\HealthChecker;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Billing\Application\UseCase\Query\CheckTBankHealth\CheckTBankHealthQuery;
use Common\Module\Billing\Application\UseCase\Query\CheckTBankHealth\TBankHealthDto;
use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Override;

/**
 * Integration Service для проверки здоровья T-Bank API.
 *
 * Реализует CheckHealthServiceInterface для регистрации в HealthCheckerRegistry.
 * Вызывает Billing Module через QueryBus согласно ADR-002.
 */
final readonly class TBankHealthCheckerService implements CheckHealthServiceInterface
{
    private const string SERVICE_NAME = 'tbank';

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

        /** @var TBankHealthDto $result */
        $result = $this->queryBus->query(new CheckTBankHealthQuery());

        $responseTimeMs = round((microtime(true) - $startTime) * 1000.0, 2);

        if ($result->isHealthy) {
            return HealthCheckResultVo::createFromOperational(
                message: 'T-Bank API is operational',
                responseTimeMs: $responseTimeMs,
            );
        }

        return HealthCheckResultVo::createFromOutage(
            message: sprintf('T-Bank API health check failed: %s', $result->errorMessage ?? 'unknown error'),
            responseTimeMs: $responseTimeMs,
        );
    }
}
