# Перечисление прав (Permission Enum)

## Определение

**Перечисление прав (Permission Enum)** — перечисление (`enum`), которое описывает доступные разрешения слоя
Presentation и используется в системе безопасности Symfony. Основано на встроенной модели ролей
[Symfony Security](https://symfony.com/doc/current/security.html#roles).

## Общие правила

- Каждый Permission Enum хранит только строковые значения с префиксом `ROLE_` или `module.action`.
- Имена кейсов пишем в `lowerCamelCase`, отражая действие (`createOwn`, `viewAll`).
- Enum объявляем `final` по умолчанию (в PHP 8.3 enum уже нельзя расширять).
- Enum лежит в каталоге `Security` рядом с Rule и Voter конкретного модуля.
- Значения Permission Enum регистрируем в `apps/web/config/packages/security.yaml` через `!php/enum`.

## Зависимости

- Разрешено: нативные возможности PHP (`enum`).
- Запрещено: любые сервисы, обращение к контейнеру, сторонние зависимости.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Security/<SubjectName>/PermissionEnum.php
```

## Как используем

1. Определяем enum с правами, разделяя сценарии `Own/All`, `View/Edit/Delete`.
2. Значения добавляем в `role_hierarchy` или проверяем напрямую через `$this->isGranted()`.
3. Enum передаём в Rule/Voter и используем в атрибутах `#[IsGranted]`.

## Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\Controller\Project\Security;

enum ProjectPermissionEnum: string
{
    case createOwn = 'project.project.createOwn';
    case createAll = 'project.project.createAll';
    case viewOwn = 'project.project.viewOwn';
    case viewAll = 'project.project.viewAll';
    case editOwn = 'project.project.editOwn';
    case editAll = 'project.project.editAll';
    case deleteOwn = 'project.project.deleteOwn';
    case deleteAll = 'project.project.deleteAll';
    case closeOwn = 'project.project.closeOwn';
    case closeAll = 'project.project.closeAll';
}
```

## Чек-лист для проведения ревью кода

- [ ] Enum лежит в каталоге Security рядом с Rule/Voter.
- [ ] Значения соответствуют принятой схеме (`module.context.actionScope`).
- [ ] Enum не содержит логики, только список разрешений.
- [ ] Все значения задекларированы в `security.yaml` через `role_hierarchy`.
- [ ] Контроллеры/Rule/Voter используют Permission Enum вместо «магических» строк.
