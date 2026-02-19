<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Service\HealthChecker;

use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;

/**
 * Интерфейс для компонентов проверки здоровья сервисов.
 */
interface CheckHealthServiceInterface
{
    /**
     * Имя проверяемого сервиса.
     */
    public function getName(): string;

    /**
     * Выполнить проверку здоровья.
     */
    public function check(): HealthCheckResultVo;
}
