<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Enum;

/**
 * Статус инцидента для системы мониторинга.
 */
enum IncidentStatusEnum: string
{
    case investigating = 'investigating';  // Инцидент расследуется
    case identified = 'identified';        // Причина определена
    case monitoring = 'monitoring';        // Решение применено, мониторинг
    case resolved = 'resolved';            // Инцидент решён
}
