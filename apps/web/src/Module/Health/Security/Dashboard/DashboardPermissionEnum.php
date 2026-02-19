<?php

declare(strict_types=1);

namespace Web\Module\Health\Security\Dashboard;

enum DashboardPermissionEnum: string
{
    case viewAll = 'health.dashboard.viewAll';
}
