<?php

declare(strict_types=1);

namespace Web\Module\Health\Security\Incident;

enum IncidentPermissionEnum: string
{
    case viewAll = 'health.incident.viewAll';
    case createAll = 'health.incident.createAll';
    case editAll = 'health.incident.editAll';
    case resolveAll = 'health.incident.resolveAll';
    case deleteAll = 'health.incident.deleteAll';
}
