<?php

declare(strict_types=1);

namespace Common\Module\Health\Integration\Service\HealthChecker;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;
use Common\Module\SpeechToText\Application\Dto\WhisperHealthDto;
use Common\Module\SpeechToText\Application\UseCase\Query\CheckWhisperHealth\CheckWhisperHealthQuery;
use Override;

/**
 * Integration Service для проверки здоровья whisper.cpp.
 *
 * Реализует CheckHealthServiceInterface для регистрации в HealthCheckerRegistry.
 * Вызывает SpeechToText Module через QueryBus согласно ADR-001.
 */
final readonly class WhisperHealthCheckerService implements CheckHealthServiceInterface
{
    private const string SERVICE_NAME = 'whisper.cpp';

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

        /** @var WhisperHealthDto $result */
        $result = $this->queryBus->query(new CheckWhisperHealthQuery());

        $responseTimeMs = round((microtime(true) - $startTime) * 1000.0, 2);

        if ($result->isHealthy) {
            $modelsInfo = $result->availableModels !== null
                ? implode(', ', $result->availableModels)
                : 'unknown';

            return HealthCheckResultVo::createFromOperational(
                message: sprintf(
                    'whisper.cpp is operational (version: %s, models: %s)',
                    $result->version ?? 'unknown',
                    $modelsInfo,
                ),
                responseTimeMs: $responseTimeMs,
            );
        }

        return HealthCheckResultVo::createFromOutage(
            message: sprintf('whisper.cpp health check failed: %s', $result->errorMessage ?? 'unknown error'),
            responseTimeMs: $responseTimeMs,
        );
    }
}
