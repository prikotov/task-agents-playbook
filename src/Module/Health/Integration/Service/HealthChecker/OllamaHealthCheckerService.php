<?php

declare(strict_types=1);

namespace Common\Module\Health\Integration\Service\HealthChecker;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Common\Module\Llm\Application\UseCase\Query\CheckOllamaHealth\CheckOllamaHealthQuery;
use Common\Module\Llm\Application\UseCase\Query\CheckOllamaHealth\OllamaHealthDto;
use Override;

/**
 * Integration Service для проверки здоровья Ollama API.
 *
 * Реализует CheckHealthServiceInterface для регистрации в HealthCheckerRegistry.
 * Вызывает Llm Module через QueryBus согласно ADR-002.
 */
final readonly class OllamaHealthCheckerService implements CheckHealthServiceInterface
{
    private const string SERVICE_NAME = 'ollama';

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

        /** @var OllamaHealthDto $result */
        $result = $this->queryBus->query(new CheckOllamaHealthQuery());

        $responseTimeMs = round((microtime(true) - $startTime) * 1000.0, 2);

        if ($result->isHealthy) {
            return HealthCheckResultVo::createFromOperational(
                message: sprintf('Ollama API is operational (%d models available)', $result->modelsCount),
                responseTimeMs: $responseTimeMs,
            );
        }

        return HealthCheckResultVo::createFromOutage(
            message: sprintf('Ollama API health check failed: %s', $result->errorMessage ?? 'unknown error'),
            responseTimeMs: $responseTimeMs,
        );
    }
}
