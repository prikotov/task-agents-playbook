# Проверка прав презентационного слоя (Presentation Authorization)

## Определение

**Проверка прав презентационного слоя (Presentation Authorization)** — способ ограничить доступ к публичным интерфейсам приложения с помощью Permission Enum, Rule, Voter и Grant. Используем встроенную модель Symfony Security, см. [Security & Authorization](https://symfony.com/doc/current/security.html).

## Архитектура авторизации

```
Controller / Template
       │
       ▼
    Grant ─────► AuthorizationChecker ◄──── Voter ◄──── Rule ◄──── PermissionEnum
       │                                            │
       └────────────────────────────────────────────┘
```

**Компоненты:**

| Компонент | Назначение | Документация |
|-----------|------------|--------------|
| **PermissionEnum** | Определяет роли `ROLE_*` для модуля | [permission_enum.md](permission_enum.md) |
| **ActionEnum** | Определяет атрибуты для `isGranted()` | [action_enum.md](action_enum.md) |
| **Rule** | Инкапсулирует логику проверки прав | [rule.md](rule.md) |
| **Voter** | Принимает решение о доступе, делегирует в Rule | [voter.md](voter.md) |
| **Grant** | Удобная обёртка для частых проверок в контроллерах/шаблонах | [grant.md](grant.md) |

## Общие правила

- Каждый модуль определяет собственный `PermissionEnum` с именами ролей `ROLE_*`.
- Логику проверки инкапсулируем в `Rule`, работающем только с `TokenInterface` и объектами Presentation.
- Решение об access принимает `Voter`, делегирующий проверку в Rule.
- Для удобства используется `Grant` — сервис-обёртка над Voter.
- Никаких прямых обращений к Domain/Infrastructure внутри Rule/Voter/Grant.

## Матрица Action-Permission

ActionEnum и PermissionEnum — независимые enum'ы. Связь между ними реализуется в [Rule](rule.md):

```
ActionEnum          Rule                 PermissionEnum
─────────          ────                 ──────────────
view         ───►  canView()    ───►    viewOwn / viewAll
edit         ───►  canEdit()    ───►    editOwn / editAll
delete       ───►  canDelete()  ───►    deleteOwn / deleteAll
```

**Пример в Rule:**

```php
public function canView(TokenInterface $token, Uuid $projectUuid): bool
{
    if ($this->hasPermission(PermissionEnum::viewAll, $token)) {
        return true;
    }
    
    return $this->hasPermission(PermissionEnum::viewOwn, $token) 
        && $this->isOwner($token, $projectUuid);
}
```

PermissionEnum добавляется в `security.yaml` и назначается ролям пользователей.

## Зависимости

- **Разрешено:** `TokenInterface`, `AuthorizationCheckerInterface`, `Web\Security\UserInterface`, `Uuid`, DTO Presentation.
- **Запрещено:** сервисы Domain, Application, ORM-репозитории, глобальные синглтоны.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Security/<SubjectName>/
├── PermissionEnum.php   # Роли модуля
├── ActionEnum.php       # Атрибуты для isGranted (view, edit, delete...)
├── Rule.php             # Логика проверки
├── Voter.php            # Голосующий объект
└── Grant.php            # Обёртка для контроллеров/шаблонов
```

## Как используем

1. Определяем [Permission Enum](permission_enum.md) и добавляем значения в `security.yaml`.
2. Определяем [Action Enum](action_enum.md) с атрибутами действий (view, edit, delete...).
3. Реализуем [Rule](rule.md) для проверки прав.
4. Создаём [Voter](voter.md), который делегирует проверку в Rule.
5. При необходимости создаём [Grant](grant.md) для удобных проверок.
6. В контроллерах вызываем `$this->isGranted(ActionEnum::view->value)` или методы Grant.

## Чек-лист для проведения ревью кода

- [ ] Permission Enum лежит в каталоге Security и содержит только значения `ROLE_*`.
- [ ] Action Enum содержит атрибуты действий (view, edit, delete...).
- [ ] Rule использует только `TokenInterface` и Presentation-типы.
- [ ] Voter делегирует проверку в Rule и зарегистрирован как сервис.
- [ ] Значения Permission Enum добавлены в `security.yaml`.
- [ ] Grant объявлен `final readonly` и не содержит бизнес-логики.
