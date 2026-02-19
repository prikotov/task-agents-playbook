<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Enum;

/**
 * Уровень серьёзности инцидента для системы мониторинга.
 */
enum IncidentSeverityEnum: string
{
    case minor = 'minor';       // Незначительный инцидент
    case major = 'major';       // Серьёзный инцидент
    case critical = 'critical'; // Критический инцидент
}
