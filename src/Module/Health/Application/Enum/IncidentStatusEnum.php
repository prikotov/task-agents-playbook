<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Enum;

/**
 * Статус инцидента для публичного API (Application layer).
 */
enum IncidentStatusEnum: string
{
    case investigating = 'investigating';
    case identified = 'identified';
    case monitoring = 'monitoring';
    case resolved = 'resolved';
}
