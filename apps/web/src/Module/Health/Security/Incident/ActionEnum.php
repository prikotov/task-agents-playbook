<?php

declare(strict_types=1);

namespace Web\Module\Health\Security\Incident;

enum ActionEnum: string
{
    case list = 'health.incident.list';
    case create = 'health.incident.create';
    case edit = 'health.incident.edit';
    case resolve = 'health.incident.resolve';
    case delete = 'health.incident.delete';
}
