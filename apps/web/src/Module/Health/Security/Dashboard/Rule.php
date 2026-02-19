<?php

declare(strict_types=1);

namespace Web\Module\Health\Security\Dashboard;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

final readonly class Rule
{
    public function __construct(
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    public function canView(TokenInterface $token): bool
    {
        return $this->hasPermission(DashboardPermissionEnum::viewAll, $token);
    }

    private function hasPermission(DashboardPermissionEnum $permission, TokenInterface $token): bool
    {
        return in_array(
            $permission->value,
            $this->roleHierarchy->getReachableRoleNames($token->getRoleNames()),
            true,
        );
    }
}
