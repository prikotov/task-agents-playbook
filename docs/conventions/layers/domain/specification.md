# Спецификация (Specification pattern)

**Спецификация ([Specification](https://martinfowler.com/apsupp/spec.pdf))** — артефакт доменного слоя, формализующий
бизнес-правило или условие применительно к объекту домена.

## Общие правила

- Используется исключительно в рамках домена.
- Не взаимодействует с базой данных и внешними сервисами.
- Не хранит изменяемое состояние (stateless). Конфигурация допускается через конструктор, бизнес-данные передаются в
  `isSatisfiedBy()`.
- Возвращает только `bool`.
- Каждая спецификация реализует одну публичную функцию: `isSatisfiedBy(mixed $value): bool`.
- Название спецификации должно ясно отражать проверяемое правило (например, `InviteResendAllowedSpecification`).
- Внедряется в потребителей через механизм DI.
- Если спецификация комбинируется с другими, можем использовать композицию (AndSpecification, OrSpecification,
  NotSpecification).

## Зависимости

- Разрешено внедрение сервисов, мапперов, фабрик и других спецификаций из своего домена (в пределах одного bounded context).
- Метод `isSatisfiedBy()` должен принимать только значения из текущего домена — примитивы, DTO, VO или Entity.
- ❗ **Запрещено** передавать данные из других модулей напрямую.
- ❗ **Запрещено** внедрять репозитории и сервисы инфраструктурного слоя.

## Расположение

- В слое [Domain](../domain.md):

```php
Common\Module\{ModuleName}\Domain\Specification\{Context}\{SpecificationName}Specification
```

`{Context}` используется при необходимости логически сгруппировать спецификации внутри модуля.

## Как используем

- Спецификации используются только в слое Domain.
- Передаются через конструктор и используются вызовом метода `isSatisfiedBy()`.
- ❗ **Запрещено** использовать спецификации из других модулей напрямую. Вместо этого допускается вызов спецификации
  через Application-слой (QueryHandler) для предотвращения
  нарушения границ домена.

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\User\Domain\Specification\TeamMembership;

use DateTimeImmutable;

final readonly class InviteResendAllowedSpecification
{
    public function __construct(
        private int $intervalMinutes = 15,
    ) {
    }

    public function isSatisfiedBy(?DateTimeImmutable $invitedAt, DateTimeImmutable $now): bool
    {
        if ($invitedAt === null) {
            return true;
        }

        return $invitedAt->modify(sprintf('+%d minutes', $this->intervalMinutes)) <= $now;
    }
}
```
