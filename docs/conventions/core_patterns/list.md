# Список (List)

**Список (List)** — класс, предназначенный для инкапсуляции набора значений какой-либо сущности в узком контексте. Например, так можно хранить список доступных значений для фильтров в UI. Важное уточнение — это не хранилище всех доступных значений и не хранилище какой-либо ещё информации, кроме набора значений.

## Общие правила

- Разрешены на уровне любого слоя (Domain, Application, Infrastructure, Integration, Presentation).
- Должен содержать только один публичный метод `all()`. Больше никаких методов в классе быть не должно.
- Возвращает `list<scalar|BackedEnum>` без ключей. Ассоциативные массивы запрещены.
- `all()` не принимает аргументов. Для контекста создай отдельный List под контекст.
- Класс не должен быть хранилищем текстовых представлений — это предназначение [Map-классов](map.md).
- Сложная логика в [Query](../layers/application/query_handler.md). Доменные условия в [Specification](../layers/domain/specification.md).
- Приватные методы запрещены. Если логика не помещается в один метод — это не List.
- Запрещены любые I/O: БД (включая чтение), HTTP, FS, очереди, внешние SDK. Запрещены env/random/time. Класс детерминированный.
- Получаем через DI.

## Расположение

Список (List) должен храниться в конкретном модуле конкретного приложения. Также допустимо использовать их в контексте конкретного хэндлера, если это необходимо для какой-либо валидации, или в Domain для бизнес-проверки.

### Domain, Application, Infrastructure, Integration

```php
Common\Module\{ModuleName}\{Layer}\List\{GroupName?}\{Name}List
```

Где:
- `{Layer}` — `Domain`, `Application`, `Infrastructure`, `Integration`
- `{GroupName?}` — опциональная группа для группировки списков
- `{Name}List` — имя списка

Примеры:
- `Common\Module\Project\Domain\List\ProjectUserRolesList`
- `Common\Module\Project\Domain\List\ProjectUserSharedRolesList`
- `Common\Module\Billing\Infrastructure\List\CurrencyList`

### Use-case-специфичные списки (Application)

Для списков, используемых только в конкретном use-case:

```php
Common\Module\{ModuleName}\Application\UseCase\{Command|Query}\{QueryGroup?}\{CaseName}\{Name}List
```

Пример:
- `Common\Module\Project\Application\UseCase\Command\ShareProject\AvailableRolesList`

### Presentation

```php
{AppName}\Module\{ModuleName}\List\{GroupName?}\{Name}List
```

Примеры:
- `Web\Module\Project\List\FastFilterProjectStatusList`
- `Web\Module\User\List\FastFilterInvitationStatusList`
- `Web\Module\Source\List\FastFilterSourceStatusList`

## Как используем

- Внедряем в потребителей через DI.
- Используем для получения набора доступных значений в узком контексте (например, для dropdown, валидации, бизнес-проверок).
- Не используем для хранения текстовых представлений или дополнительных метаданных — для этого существуют [Map-классы](map.md).
- Не используем для запросов в БД или сложной логики — для этого создавайте [Query](../layers/application/query_handler.md).

## Правила именования

Имя класса должно включать:
1. Контекст (если применимо)
2. Название сущности или предметной области
3. Суффикс `List`

Примеры имён классов:
- `ProjectUserRolesList`
- `FastFilterProjectStatusList`

## Пример

List-класс для ролей пользователей проекта (Domain):

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Domain\List;

use Common\Module\Project\Domain\Enum\ProjectUserTypeEnum;

final class ProjectUserRolesList
{
    /**
     * @return list<ProjectUserTypeEnum>
     */
    public function all(): array
    {
        return [
            ProjectUserTypeEnum::owner,
            ProjectUserTypeEnum::editor,
            ProjectUserTypeEnum::viewer,
        ];
    }
}
```

List-класс для фильтрации проектов по статусу (Presentation):

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\List;

final class FastFilterProjectStatusList
{
    /**
     * @return list<FastFilterProjectStatusEnum>
     */
    public function all(): array
    {
        return [
            FastFilterProjectStatusEnum::all,
            FastFilterProjectStatusEnum::new,
            FastFilterProjectStatusEnum::active,
            FastFilterProjectStatusEnum::closed,
            FastFilterProjectStatusEnum::deleted,
        ];
    }
}
```

## Чек-лист код-ревью

- [ ] Класс `final`, без свойств и состояния..
- [ ] Содержит только один публичный метод `all()`.
- [ ] all() не принимает аргументов.
- [ ] Метод возвращает `list<scalar|BackedEnum>` без ключей.
- [ ] Нет приватных методов (признак необходимости refactor в query-handler).
- [ ] Нет обращений к БД, HTTP, файлам и другим внешним ресурсам.
- [ ] Не используется для текстовых представлений или метаданных (для этого Map-классы).
- [ ] Namespace соответствует шаблону.
- [ ] Имя класса соответствует правилам именования.
