# Хелпер (Helper)

**Хелпер (Helper)** — класс только со статическими методами для детерминированных утилитных операций внутри одного модуля (без I/O и скрытых входов).

## Общие правила

- В названии класса указывается постфикс `Helper`.
- Класс final, все методы public static.
- Один Helper — один узкий контекст (SRP).
- Методы детерминированы и без побочных эффектов (pure).
- Только технические преобразования, бизнес-логика запрещена.
- Нет состояния и DI. Нужны зависимости/состояние — создавайте [сервис](service.md).
- Не является “общим” утилитным классом для всего проекта. Только внутри модуля/слоя.
- Методы не должны зависеть от конфигурации окружения и runtime-контекста приложения.

## Зависимости

**Разрешено**:
- Примитивы, VO и Enum своего модуля.
- Стандартные функции PHP, не выполняющие I/O и не читающие окружение.

**Запрещено**:
- Хранить состояние (stateful): static-свойства, кеши, счётчики, singletons.
- DI и любые зависимости на сервисы/контейнер.
- Любой I/O запрещён (FS, сеть, БД, очереди, внешние API/SDK).
- Бизнес-процессы.

## Расположение

Хелпер размещается в слое, где используется его логика. Примеры:

**Domain слой** — утилитные методы для доменных сущностей и VO:

```
Common\Module\{ModuleName}\Domain\Helper\{Name}Helper
```

**Application слой** — утилитные методы для use case и DTO:

```
Common\Module\{ModuleName}\Application\Helper\{Name}Helper
Common\Module\{ModuleName}\Application\UseCase\Query\{UseCase}\Helper\{Name}Helper
Common\Module\{ModuleName}\Application\UseCase\Command\{UseCase}\Helper\{Name}Helper
```

**Infrastructure слой** — утилитные методы для инфраструктурных операций:

```
Common\Module\{ModuleName}\Infrastructure\Service\{Context}\Helper\{Name}Helper
```

## Как используем

- Группирует утилиты одного узкого контекста.
- Используется через статический вызов.
- В DI не регистрируется.
- Хелпер не должен заменять сервисы — если нужна инъекция зависимостей, создавайте сервис.
- Методы именуются глаголами и описывают преобразование/вычисление (например, normalize*, parse*, build*, format*).

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\Source\Domain\Helper;

final class FileHelper
{
    public static function removeExtension(string $filename): string
    {
        $pathInfo = pathinfo($filename);
        $extension = $pathInfo['extension'] ?? null;
        if ($extension !== null && str_ends_with($filename, '.' . $extension)) {
            $filename = substr($filename, 0, -(strlen($extension) + 1));
        }

        return $filename;
    }
}
```

## Чек-лист для ревью кода

- [ ] Класс имеет постфикс `Helper` и находится в правильном слое.
- [ ] Класс помечен как `final`.
- [ ] Все методы статические.
- [ ] Нет конструктора и внедрения зависимостей.
- [ ] Нет изменений внешнего состояния и I/O-операций.
- [ ] Нет скрытых входов. Нет побочных эффектов.
- [ ] Нет бизнес-логики домена — только утилитные операции.
- [ ] Unit-тесты обязательны при ветвлениях и логике.
