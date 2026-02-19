<?php

declare(strict_types=1);

namespace Web\Module\Health\Route;

final readonly class DashboardRoute
{
    public const string DASHBOARD_PATH = '/admin/health/dashboard';
    public const string DASHBOARD = 'admin_health_dashboard';

    public function dashboard(): string
    {
        return self::DASHBOARD_PATH;
    }
}
