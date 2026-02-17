# Трейт (Trait)

**Трейт (Trait)** — механизм горизонтального повторного использования кода в PHP, позволяющий включать наборы методов в несколько классов без использования наследования.

Источник: [PHP Manual - Traits](https://www.php.net/manual/ru/language.oop5.traits.php)

## Общие правила

- Трейт — для маленьких технических миксинов (обычно 1–3 публичных метода), когда композиция даёт неоправданный оверхед.
- Предпочитайте композицию трейтам, если требуется состояние/зависимости/вариативность поведения (стратегии, полиморфизм).
- Если трейт добавляет публичные методы, класс-носитель должен реализовать соответствующий интерфейс, чтобы контракт был явным (без "магии из use").
- ❗ **Запрещено**:
  - бизнес-логика;
  - сложная техническая логика;
  - много методов в одном трейте;
  - зависеть от API хоста (методы/свойства/константы), включая `parent::` и требования к наличию `protected` членов. Трейт может использовать только свои `private` свойства/методы.
- Трейт автономен: не требует методов/свойств хоста, не опирается на окружение, не тянет инфраструктуру. Если трейт становится "общим" — переносите его в `Common\Component\*`.
- Именование: постфикс `Trait`. Название отражает поведение, а не тип данных (например, `SortableCriteriaTrait`, `HasAuditTrailTrait`).
- Свойства трейта — только `private` (защищённые повышают риск коллизий/неявных зависимостей). Публичные свойства запрещены.
- Методы трейта должны быть простыми и выполнять одну операцию.

## Зависимости

- Разрешены: примитивы, `Enum`, `VO`, интерфейсы, простые типы данных.
- Запрещены: внешние сервисы/инфраструктура (DI, HTTP, FS, очереди). Исключение: инфраструктурные трейты допускаются в слое Infrastructure.
- Запрещены скрытые источники данных: `ClockInterface`/`new DateTimeImmutable()` внутри трейта, `random*`, `getenv`/`$_ENV`/`$_SERVER`, глобальные синглтоны, `static`-кеши.

## Расположение

Трейт размещается в слое, где используется. Примеры:

- Общие трейты:

```
Common\Component\{ComponentName}\Trait\{Name}Trait
```

- Трейты для консольного приложения:

```
Console\Module\{ModuleName}\Trait\{Name}Trait
```

- Трейты модулей:

```
Common\Module\{ModuleName}\{Layer}\Trait\{Name}Trait
```

## Как используем

- Включайте трейт через ключевое слово `use` внутри класса.
- При тестировании классов с трейтами тестируйте поведение класса, а не трейт отдельно.
- Ограничения на содержание трейтов см. в разделе "Общие правила".

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Component\Repository\Trait;

use Common\Component\Repository\Enum\SortEnum;

trait SortableCriteriaTrait
{
    /**
     * @var array<string, SortEnum>
     */
    private array $order = [];

    /**
     * @param array<string, SortEnum> $order
     */
    public function setSort(array $order): void
    {
        $this->order = $order;
    }

    /**
     * @return array<string, SortEnum>
     */
    public function getSort(): array
    {
        return $this->order;
    }
}
```

Использование трейта:

```php
<?php

declare(strict_types=1);

namespace Common\Module\User\Domain\Repository\User\Criteria;

use Common\Component\Repository\CriteriaWithLimitInterface;
use Common\Component\Repository\CriteriaWithOffsetInterface;
use Common\Component\Repository\SortableCriteriaInterface;
use Common\Component\Repository\Trait\CriteriaWithLimitTrait;
use Common\Component\Repository\Trait\CriteriaWithOffsetTrait;
use Common\Component\Repository\Trait\SortableCriteriaTrait;
use Common\Module\User\Domain\Repository\User\UserCriteriaInterface;

final class UserFindCriteria implements
    UserCriteriaInterface,
    SortableCriteriaInterface,
    CriteriaWithLimitInterface,
    CriteriaWithOffsetInterface
{
    use SortableCriteriaTrait;
    use CriteriaWithLimitTrait;
    use CriteriaWithOffsetTrait;

    public function __construct(
        private ?string $email = null,
    ) {}

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
```

Пример трейта для ORM-сущности:

```php
<?php

declare(strict_types=1);

namespace Common\Component\Doctrine\Trait;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ValueError;

trait InsTsTrait
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private readonly ?DateTimeImmutable $insTs;

    public function getInsTs(): DateTimeImmutable
    {
        if (is_null($this->insTs)) {
            throw new ValueError("insTs is not initialized");
        }

        return $this->insTs;
    }
}
```

## Чек лист для проведения ревью кода

- [ ] Трейт содержит только простые методы без бизнес-логики.
- [ ] Именование с постфиксом `Trait`; корректное расположение в соответствующей директории.
- [ ] Трейт не зависит от родительского класса и не знает о его методах/свойствах.
- [ ] Трейт автономен и не требует ни методов/свойств хоста, ни внешних зависимостей; его можно подключить без дополнительной подготовки класса.
- [ ] Количество методов в трейте минимально (обычно 1–3 публичных метода).
- [ ] Свойства трейта только `private`, не `protected` и не публичные.
- [ ] Публичные методы трейта отражены интерфейсом (контракт явный).
- [ ] Трейт не вводит `protected`-состояние и не создаёт точек расширения через неявные зависимости.
- [ ] В трейте нет обращений к `static`, глобальному состоянию и инфраструктуре (включая DI).
- [ ] Нет конфликтов имён: методы/свойства трейта названы так, чтобы минимизировать коллизии (узкий контекст, префиксы при необходимости).
- [ ] Если трейт хранит состояние — оно имеет безопасное значение по умолчанию и не требует конструктора.
- [ ] Для функциональности трейта нет более подходящего решения через композицию.
