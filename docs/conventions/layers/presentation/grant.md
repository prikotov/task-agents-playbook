# Грант-сервис (Grant Service)

## Определение

**Грант-сервис (Grant Service)** — вспомогательный сервис презентационного слоя, который агрегирует проверки прав для конкретной сущности, повторно используя [Symfony AuthorizationChecker](https://symfony.com/doc/current/security.html#checking-user-roles). Он обеспечивает единообразный доступ к разрешениям на уровне шаблонов и контроллеров.

## Общие правила

- Класс объявляется `final readonly` и хранит только зависимости через конструктор.
- Методы именуются с префиксом `can*` и возвращают `bool` без побочных эффектов.
- Каждый метод инкапсулирует вызов `AuthorizationCheckerInterface::isGranted()` и дополнительные флаги (например, soft/hard delete).
- Внутри не используем `TokenInterface` напрямую — только необходимые идентификаторы (`Uuid`) или DTO презентационного слоя.
- Не выполняем запросы к базе, не обращаемся к Domain/Application, не модифицируем состояние.

## Зависимости

- Разрешено: `AuthorizationCheckerInterface`, enum-значения действий (`*ActionEnum`), простые типы (`Uuid`, DTO, флаги состояния).
- Запрещено: репозитории, QueryBus/CommandBus, сервисы Domain/Application/Infrastructure, обращения к глобальному состоянию.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Security/<SubjectName>/Grant.php
```

- Имя файла совпадает с контекстом (`Security/User/Grant.php`, `Security/Project/Grant.php`).
- Хранится в каталоге `Security/<SubjectName>` рядом с Permission Enum, ActionEnum, Rule и Voter.

## Как используем

1. Создаём Grant для сущности и регистрируем его как сервис в модуле.
2. Внедряем Grant в контроллеры, Twig-шаблоны и компоненты UI через DI.
3. Вызываем методы `can*`, чтобы скрыть/показать действия (кнопки, ссылки, формы).
4. Для сложных сценариев (soft/hard delete) комбинируем проверки внутри Grant, не вынося условие в шаблоны.

## Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\User\Security\User;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

// ActionEnum определён в том же каталоге (`Security/User/ActionEnum.php`).

final readonly class Grant
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function canEdit(Uuid $userUuid): bool
    {
        return $this->authorizationChecker->isGranted(ActionEnum::edit->value, $userUuid);
    }

    public function canSoftDelete(Uuid $userUuid, bool $isDeleted): bool
    {
        return !$isDeleted && $this->canDelete($userUuid);
    }

    public function canDelete(Uuid $userUuid): bool
    {
        return $this->authorizationChecker->isGranted(ActionEnum::delete->value, $userUuid);
    }

    public function canVerify(Uuid $userUuid): bool
    {
        return $this->authorizationChecker->isGranted(ActionEnum::verify->value, $userUuid);
    }
}
```

## Чек-лист для проведения ревью кода

- [ ] Grant лежит в каталоге `Security` соответствующего модуля и объявлен `final readonly`.
- [ ] Все публичные методы начинаются с `can*` и возвращают `bool`.
- [ ] Внутри используются значения `*ActionEnum`, а не строки.
- [ ] Нет зависимостей на Domain/Application/Infrastructure-сервисы.
- [ ] Шаблоны и контроллеры обращаются к Grant вместо прямых вызовов `is_granted()`.
