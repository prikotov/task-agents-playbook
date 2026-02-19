<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Component\RabbitMqHealthCheck;

use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;

/**
 * Интерфейс компонента для проверки здоровья RabbitMQ.
 */
interface RabbitMqHealthCheckComponentInterface
{
    /**
     * Выполняет проверку здоровья RabbitMQ.
     *
     * @return HealthCheckResultVo Результат проверки
     */
    public function check(): HealthCheckResultVo;
}
