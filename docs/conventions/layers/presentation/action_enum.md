# Перечисление действий (Action Enum)

## Определение

**Перечисление действий (Action Enum)** — enum в презентационном слое, определяющий список действий (view, edit, delete...) для проверки прав через `isGranted()`. Используется в Voter и Grant вместо строковых значений.

## Общие правила

- Имя класса всегда `ActionEnum`.
- Каждый case — действие над сущностью (view, edit, delete, create, close...).
- Значение case — строка в формате `{module}.{subject}.{action}`.

## Зависимости

- Не имеет зависимостей — чистый enum.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Security/<SubjectName>/ActionEnum.php
```

## Как используем

1. Определяем список действий для сущности.
2. В [Voter](voter.md) проверяем `ActionEnum::tryFrom($attribute)` в методе `supports()`.
3. В методе `voteOnAttribute()` используем match для маршрутизации проверки в [Rule](rule.md).
4. В [Grant](grant.md) вызываем `isGranted(ActionEnum::edit->value, $subject)`.

## Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\Security\Project;

enum ActionEnum: string
{
    case create = 'project.project.create';
    case view = 'project.project.view';
    case edit = 'project.project.edit';
    case delete = 'project.project.delete';
    case close = 'project.project.close';
}
```

### Использование в [Voter](voter.md)

```php
#[Override]
protected function supports(string $attribute, mixed $subject): bool
{
    return ActionEnum::tryFrom($attribute) !== null && $this->isSubjectValid($subject);
}

#[Override]
protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
{
    $action = ActionEnum::tryFrom($attribute);
    if ($action === null) {
        return false;
    }

    return match ($action) {
        ActionEnum::create => $this->rule->canCreate($token, $subject),
        ActionEnum::view => $this->rule->canView($token, $subject),
        ActionEnum::edit => $this->rule->canEdit($token, $subject),
        ActionEnum::delete => $this->rule->canDelete($token, $subject),
        ActionEnum::close => $this->rule->canClose($token, $subject),
    };
}
```

### Использование в [Grant](grant.md)

```php
public function canEdit(Uuid $projectUuid): bool
{
    return $this->authorizationChecker->isGranted(
        ActionEnum::edit->value,
        ['projectUuid' => $projectUuid],
    );
}
```

### Использование в контроллере

```php
if (!$this->isGranted(ActionEnum::edit->value, ['projectUuid' => $projectUuid])) {
    throw $this->createAccessDeniedException();
}
```

## Чек-лист для проведения ревью кода

- [ ] Класс назван `ActionEnum`, лежит в `apps/<app>/src/Module/<ModuleName>/Security/<SubjectName>/`.
- [ ] Значения case в формате `{module}.{subject}.{action}`.
- [ ] Voter использует `ActionEnum::tryFrom()` в `supports()`.
- [ ] Grant использует `ActionEnum::*->value` при вызове `isGranted()`.
