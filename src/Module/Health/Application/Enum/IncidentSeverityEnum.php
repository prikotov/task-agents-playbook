<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Enum;

/**
 * Уровень серьёзности инцидента для публичного API (Application layer).
 */
enum IncidentSeverityEnum: string
{
    case minor = 'minor';
    case major = 'major';
    case critical = 'critical';
}
