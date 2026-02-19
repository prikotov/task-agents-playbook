<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Enum;

/**
 * Статус сервиса для мониторинга.
 */
enum ServiceStatusEnum: string
{
    case operational = 'operational';   // Сервис работает нормально
    case degraded = 'degraded';         // Сервис работает с ограничениями
    case outage = 'outage';             // Сервис недоступен
    case maintenance = 'maintenance';   // Сервис на обслуживании
}
