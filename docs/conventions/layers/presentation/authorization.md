# Проверка прав презентационного слоя (Presentation Authorization)

## Определение

**Проверка прав презентационного слоя (Presentation Authorization)** — способ ограничить доступ к
публичным интерфейсам приложения с помощью Permission Enum, Rule и Voter. Используем встроенную модель
Symfony Security, см. [Security & Authorization](https://symfony.com/doc/current/security.html).

## Общие правила

- Каждый модуль определяет собственный `PermissionEnum` с именами ролей `ROLE_*`.
- Логику проверки инкапсулируем в `Rule`, работающем только с `TokenInterface` и объектами Presentation.
- Решение об access принимает `Voter`, делегируя проверку в Rule.
- Контроллеры вызывают `$this->isGranted()` или используют атрибут `#[IsGranted]`.
- Никаких прямых обращений к Domain/Infrastructure внутри Rule/Voter.

## Зависимости

- Разрешено: `TokenInterface`, `Web\Security\UserInterface`, `Uuid`, DTO Presentation.
- Запрещено: сервисы Domain, Application, ORM-репозитории, глобальные синглтоны.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Security/<SubjectName>/
```

- `PermissionEnum` — `<SubjectName>/PermissionEnum.php`.
- `ActionEnum` — `<SubjectName>/ActionEnum.php`, хранит список атрибутов `isGranted`.
- `Rule` — `<SubjectName>/Rule.php`, `final readonly`.
- `Voter` — `<SubjectName>/Voter.php`, расширяет `Symfony\Component\Security\Core\Authorization\Voter\Voter`.
- `Grant` — `<SubjectName>/Grant.php`, инкапсулирует повторно используемые проверки.

## Как используем

1. Определяем Permission Enum (см. [«Перечисление прав»](permission_enum.md)) и добавляем значения в `apps/web/config/packages/security.yaml` через `!php/enum`.
2. Реализуем Rule (см. [«Правило доступа»](rule.md)), принимающий `TokenInterface` и проверяющий права текущего пользователя.
3. Создаём Voter (см. [«Голосующий объект»](voter.md)), который вызывает Rule.
4. В контроллерах и других точках Presentation вызываем `$this->isGranted(PermissionEnum::case->value, $subject)`.
5. Для текущего пользователя всегда используем `#[CurrentUser] UserInterface $currentUser`.

## Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\User\Security\User;

use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter as SymfonyVoter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Uid\Uuid;
use Web\Security\UserInterface;

enum PermissionEnum: string
{
    case viewOwn = 'user.user.viewOwn';
    case viewAll = 'user.user.viewAll';
    case deleteAll = 'user.user.deleteAll';
}

enum ActionEnum: string
{
    case view = 'user.user.view';
    case delete = 'user.user.delete';
}

final readonly class Rule
{
    public function __construct(private RoleHierarchyInterface $roleHierarchy)
    {
    }

    public function canView(TokenInterface $token, Uuid $userUuid): bool
    {
        if ($this->hasPermission(PermissionEnum::viewAll, $token)) {
            return true;
        }

        return $this->hasPermission(PermissionEnum::viewOwn, $token) && $this->isSameUser($token, $userUuid);
    }

    public function canDelete(TokenInterface $token): bool
    {
        return $this->hasPermission(PermissionEnum::deleteAll, $token);
    }

    private function hasPermission(PermissionEnum $permissionEnum, TokenInterface $token): bool
    {
        return in_array(
            $permissionEnum->value,
            $this->roleHierarchy->getReachableRoleNames($token->getRoleNames()),
            true,
        );
    }

    private function isSameUser(TokenInterface $token, Uuid $userUuid): bool
    {
        $user = $token->getUser();

        return $user instanceof UserInterface && $user->getUuid()->equals($userUuid);
    }
}

/**
 * @template-extends Voter<string, Uuid>
 */
final class Voter extends SymfonyVoter
{
    public function __construct(private readonly Rule $rule)
    {
    }

    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return ActionEnum::tryFrom($attribute) !== null && $subject instanceof Uuid;
    }

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Uuid) {
            return false;
        }

        $action = ActionEnum::tryFrom($attribute);
        if ($action === null) {
            return false;
        }

        return match ($action) {
            ActionEnum::view => $this->rule->canView(token: $token, userUuid: $subject),
            ActionEnum::delete => $this->rule->canDelete(token: $token),
            default => false,
        };
    }
}
```

## Чек-лист для проведения ревью кода

- [ ] Permission Enum лежит в каталоге Security и содержит только значения `ROLE_*`.
- [ ] Rule использует только `TokenInterface` и Presentation-типы, без доступа к Domain.
- [ ] Voter делегирует проверку в Rule и зарегистрирован как сервис.
- [ ] Значения Permission Enum добавлены в `security.yaml`.
- [ ] Контроллеры вызывают `$this->isGranted()` / `#[IsGranted]`, принимая `#[CurrentUser]` без nullable.
