<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Enum;

/**
 * Статус сервиса для Application слоя.
 * Используется в DTO для изоляции от Domain слоя.
 */
enum ServiceStatusEnum: string
{
    case operational = 'operational';
    case degraded = 'degraded';
    case outage = 'outage';
    case maintenance = 'maintenance';
}
