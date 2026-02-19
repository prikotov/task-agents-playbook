<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Component\DatabaseHealthCheck;

use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;

/**
 * Интерфейс компонента для проверки здоровья PostgreSQL.
 */
interface DatabaseHealthCheckComponentInterface
{
    /**
     * Выполнить проверку соединения с базой данных.
     *
     * Использует Doctrine DBAL для выполнения простого запроса SELECT 1.
     */
    public function check(): HealthCheckResultVo;
}
