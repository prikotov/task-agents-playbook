# Объект-Значение (Value Object)

**Объект-Значение (Value Object, VO)** — неизменяемый объект, определяемый своими значениями, инкапсулирующий инварианты, валидацию и простые операции над своими значениями..

Источник: [Martin Fowler - ValueObject](https://martinfowler.com/bliki/ValueObject.html)

## Общие правила

- Класс `final readonly`, свойства приватные и строго типизированные.
- Нет идентификатора. Равенство определяется по значениям всех атрибутов. При необходимости, если VO участвует в сравнениях или бизнес-условиях, добавляйте явный метод `equals(self $other): bool`.
- Инициализация через конструктор или статические фабрики; сеттеры отсутствуют.
- Если требуется несколько форматов создания или строгий контроль инвариантов, используйте статические фабричные методы с префиксом `createFrom*()` и объявляйте конструктор `private`.
- Валидируйте и нормализуйте входные данные в конструкторе или фабричном методе. При нарушении инвариантов или некорректных входных данных выбрасывайте `InvalidArgumentException`.
- Запрещены побочные эффекты и I/O; допускаются только чистые операции над собственными значениями.
- Допустимы только простые и детерминированные преобразования и нормализация (например, тримминг, `mb_strtolower` для e-mail).
- Именование: постфикс `Vo` (например, EmailVo, MoneyVo) для явного отделения от Entity, DTO и других типов классов.
- Сериализация при необходимости — через `JsonSerializable`.

## Зависимости

- Разрешены примитивы, `DateTimeImmutable`, `Uuid`, `Enum` и другие VO того же слоя.
- Запрещены Infrastructure, ORM/DB, внешние SDK, контейнер DI и любое глобальное состояние.

## Расположение

- VO [слоя Domain](docs/conventions/layers/domain.md):

```
Common\Module\{ModuleName}\Domain\ValueObject\{Name}Vo
```

- Встраиваемые VO для ORM (Embeddable), применяемые внутри моделей сущностей:

```
Common\Module\{ModuleName}\Domain\Entity\ValueObject\{Name}Vo
```

- VO [слоя Application](docs/conventions/layers/application.md) (для представления сложных типов данных и инкапсуляции форматов представления, без бизнес-логики домена):

```
Common\Module\{ModuleName}\Application\ValueObject\{Name}Vo
Common\Module\{ModuleName}\Application\Dto\{Name}DtoVo
```

- VO [слоя Presentation](docs/conventions/layers/presentation.md) (если необходимы объекты представления):

```
Web\\Module\\{ModuleName}\\...\\ValueObject\\{Name}Vo
Api\\v1\\Module\\{ModuleName}\\...\\ValueObject\\{Name}Vo
Console\\Module\\{ModuleName}\\...\\ValueObject\\{Name}Vo
```

- VO [слоя Infrastructure](docs/conventions/layers/infrastructure.md) (служебные VO для компонентов/адаптеров):

```
Common\\Module\\{ModuleName}\\Infrastructure\\Component\\{Component}\\Vo\\{Name}Vo
Common\\Module\\{ModuleName}\\Infrastructure\\...\\ValueObject\\{Name}Vo
```

### Группировка сложных VO

Если VO состоит из нескольких классов (вспомогательные типы/подтипы), группируйте их по смыслу в подкаталоге:

```
Common\Module\{ModuleName}\Domain\ValueObject\{GroupName}\{Name}Vo
Common\Module\{ModuleName}\Domain\Entity\ValueObject\{GroupName}\{Name}Vo
```

## Как используем

- Используем как любой другой тип данных в пределах соответствующего слоя.
- Соблюдаем изоляцию слоёв: Value Object используются только в пределах своего слоя.
- [Сущности (Entity)](../layers/domain/entity.md) хранят VO в полях; изменения состояния — через методы сущности, создающие новые VO при необходимости.
- Для персистентности (только для Embeddable VO): составные VO объявляются как `#[Embeddable]` внутри ORM-моделей.

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\User\Domain\ValueObject;

use InvalidArgumentException;

final readonly class EmailVo
{
    private function __construct(
        private string $value,
    ) {}

    public static function createFromEmail(string $email): self
    {
        $email = mb_strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format.');
        }
        return new self($email);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

## Чек лист для проведения ревью кода

- [ ] Класс `final readonly`, без сеттеров и побочных эффектов.
- [ ] Именование с постфиксом `Vo`; корректное расположение в соответствующем слое.
- [ ] Валидация и нормализация данных выполняются при создании; при ошибке — `InvalidArgumentException`.
- [ ] Зависимости только примитивы, `DateTimeImmutable`, `Uuid`, `Enum` и другие VO этого слоя; нет ссылок на другие слои/Infrastructure/ORM/SDK.
- [ ] VO слоя Application не содержат бизнес-логики домена и не выражают доменные инварианты — только форматирование и преобразование данных.
- [ ] Методы чистые (pure); не изменяют состояние объекта и не зависят от внешнего контекста.
- [ ] При необходимости предусмотрены `equals()` / `JsonSerializable` / методы преобразования (например, `toString()`).
- [ ] Для персистентности (только Embeddable VO): используется `#[Embeddable]` в ORM‑моделях.
