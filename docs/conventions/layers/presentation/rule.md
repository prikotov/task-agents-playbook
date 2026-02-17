# Правило доступа (Access Rule)

## Определение

**Правило доступа (Access Rule)** — сервис слоя Presentation, инкапсулирующий бизнес-логику проверки прав.
Rule опирается на Permission Enum и инфраструктуру Symfony Security, но не зависит от Domain-слоя. См.
[Symfony Security Authorization](https://symfony.com/doc/current/security.html#authorization).

## Общие правила

- Класс объявляем `final readonly`.
- Внедряем только сервисы Presentation/Application, необходимые для проверки (RoleHierarchy, QueryBus и т.п.).
- Методы именуем `can<Action>` (`canCreate`, `canViewOwn`) и принимают `TokenInterface` + предмет проверки.
- Внутри Rule используем Permission Enum и дополнительные проверки (например, принадлежность к проекту).
- Rule не обращается напрямую к контроллерам или представлениям.

## Зависимости

- Разрешено: `TokenInterface`, `RoleHierarchyInterface`, публичные Application-компоненты (QueryBus/CommandBus), DTO Presentation.
- Запрещено: репозитории Domain, Entity Manager, внешние сервисы без адаптеров Presentation.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Security/<SubjectName>/Rule.php
```

## Как используем

1. Внедряем Rule в Voter и/или контроллер.
2. Методы Rule вызываем из Voter или специфических сервисов Presentation.
3. Rule может обращаться к Application через QueryBus для проверки дополнительных условий (например, членство).
4. Возвращаем `bool`, не бросаем исключений — Voter решает итоговый вердикт.

## Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\Controller\Project\Security;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Project\Application\UseCase\Query\ProjectUser\CheckMember\CheckMemberQuery;
use Common\Module\Project\Application\UseCase\Query\ProjectUser\CheckOwner\CheckOwnerQuery;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ProjectRule
{
    public function __construct(
        private RoleHierarchyInterface $roleHierarchy,
        private QueryBusComponentInterface $queryBus,
    ) {
    }

    public function canCreate(TokenInterface $token, ?Uuid $userUuid): bool
    {
        return $this->canCreateAll($token) || $this->canCreateOwn($token, $userUuid);
    }

    public function canCreateAll(TokenInterface $token): bool
    {
        return $this->hasPermission(ProjectPermissionEnum::createAll, $token);
    }

    public function canCreateOwn(TokenInterface $token, ?Uuid $userUuid): bool
    {
        $hasPermission = $this->hasPermission(ProjectPermissionEnum::createOwn, $token);
        if (!$hasPermission) {
            return false;
        }
        if ($userUuid === null) {
            return true;
        }
        return $this->hasAccessToUserProjects($token, $userUuid);
    }

    public function canView(TokenInterface $token, ?Uuid $userUuid = null, ?Uuid $projectUuid = null): bool
    {
        return $this->canViewAll($token) || $this->canViewOwn($token, $userUuid, $projectUuid);
    }

    public function canViewAll(TokenInterface $token): bool
    {
        return $this->hasPermission(ProjectPermissionEnum::viewAll, $token);
    }

    public function canViewOwn(TokenInterface $token, ?Uuid $userUuid = null, ?Uuid $projectUuid = null): bool
    {
        $hasPermission = $this->hasPermission(ProjectPermissionEnum::viewOwn, $token);
        if (!$hasPermission) {
            return false;
        }

        if ($userUuid !== null) {
            $hasPermission = $this->hasAccessToUserProjects($token, $userUuid);
            if (!$hasPermission) {
                return false;
            }
        }

        if ($projectUuid !== null) {
            $hasPermission = $this->hasViewAccessToProject($token, $projectUuid);
            if (!$hasPermission) {
                return false;
            }
        }

        return true;
    }

    public function canEdit(TokenInterface $token, ?Uuid $projectUuid): bool
    {
        return $this->canEditAll($token) || $this->canEditOwn($token, $projectUuid);
    }

    public function canEditAll(TokenInterface $token): bool
    {
        return $this->hasPermission(ProjectPermissionEnum::editAll, $token);
    }

    public function canEditOwn(TokenInterface $token, ?Uuid $projectUuid): bool
    {
        $hasPermission = $this->hasPermission(ProjectPermissionEnum::editOwn, $token);
        if (!$hasPermission) {
            return false;
        }
        if ($projectUuid === null) {
            return true;
        }
        return $this->hasAccessToProject($token, $projectUuid);
    }

    public function canDelete(TokenInterface $token, ?Uuid $projectUuid): bool
    {
        return $this->canDeleteAll($token) || $this->canDeleteOwn($token, $projectUuid);
    }

    public function canDeleteAll(TokenInterface $token): bool
    {
        return $this->hasPermission(ProjectPermissionEnum::editAll, $token);
    }

    public function canDeleteOwn(TokenInterface $token, ?Uuid $projectUuid): bool
    {
        $hasPermission = $this->hasPermission(ProjectPermissionEnum::editOwn, $token);
        if (!$hasPermission) {
            return false;
        }
        if ($projectUuid === null) {
            return true;
        }
        return $this->hasAccessToProject($token, $projectUuid);
    }

    private function hasPermission(ProjectPermissionEnum $permissionEnum, TokenInterface $token): bool
    {
        return in_array(
            $permissionEnum->value,
            $this->roleHierarchy->getReachableRoleNames($token->getRoleNames()),
            true,
        );
    }

    private function hasAccessToUserProjects(TokenInterface $token, Uuid $userUuid): bool
    {
        $user = $token->getUser();

        if ($user === null) {
            return false;
        }

        return $user->getUuid()->toString() === $userUuid->toString();
    }

    private function hasAccessToProject(TokenInterface $token, Uuid $projectUuid): bool
    {
        $user = $token->getUser();

        if ($user === null) {
            return false;
        }

        return $this->queryBus->query(new CheckOwnerQuery(
            projectUuid: $projectUuid,
            ownerUuid: $user->getUuid(),
        ));
    }

    private function hasViewAccessToProject(TokenInterface $token, Uuid $projectUuid): bool
    {
        $user = $token->getUser();

        if ($user === null) {
            return false;
        }

        return $this->queryBus->query(new CheckMemberQuery(
            projectUuid: $projectUuid,
            userUuid: $user->getUuid(),
        ));
    }
}
```

## Чек-лист для проведения ревью кода

- [ ] Rule объявлен `final readonly` и находится в каталоге Security.
- [ ] Все публичные методы начинаются с `can*` и возвращают `bool`.
- [ ] Используется Permission Enum, а не строки.
- [ ] Дополнительные проверки выполняются через Application (QueryBus) или Presentation сервисы.
- [ ] Rule не использует классы Domain/Infrastructure и не бросает исключения при отказе.
