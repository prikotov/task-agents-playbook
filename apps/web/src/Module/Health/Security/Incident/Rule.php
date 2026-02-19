<?php

declare(strict_types=1);

namespace Web\Module\Health\Security\Incident;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

final readonly class Rule
{
    public function __construct(
        private RoleHierarchyInterface $roleHierarchy,
    ) {
    }

    public function canList(TokenInterface $token): bool
    {
        return $this->hasPermission(IncidentPermissionEnum::viewAll, $token);
    }

    public function canCreate(TokenInterface $token): bool
    {
        return $this->hasPermission(IncidentPermissionEnum::createAll, $token);
    }

    public function canEdit(TokenInterface $token): bool
    {
        return $this->hasPermission(IncidentPermissionEnum::editAll, $token);
    }

    public function canResolve(TokenInterface $token): bool
    {
        return $this->hasPermission(IncidentPermissionEnum::resolveAll, $token);
    }

    public function canDelete(TokenInterface $token): bool
    {
        return $this->hasPermission(IncidentPermissionEnum::deleteAll, $token);
    }

    private function hasPermission(IncidentPermissionEnum $permission, TokenInterface $token): bool
    {
        return in_array(
            $permission->value,
            $this->roleHierarchy->getReachableRoleNames($token->getRoleNames()),
            true,
        );
    }
}
