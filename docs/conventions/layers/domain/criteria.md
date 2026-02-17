# Критерий (Criteria)

**Критерий (Criteria)** - объект, инкапсулирующий условия выборки сущностей в репозитории. Используются для фильтрации,
сортировки и пагинации. Позволяют избежать множества специализированных методов (`findByEmailAndStatus`) и делает
выборки
расширяемыми и типобезопасными.

## Общие правила

- **Структура:** интерфейс и реализации в `Domain`, имя `{Entity}{Context}Criteria`.
- **Использование:** Репозиторий принимает интерфейс критерия. Один критерий описывает только один контекст выборки.
- **Ограничения:** без бизнес-логики, только фильтры/пагинация/сортировка.
- **Способ задания:** через `set*()` или конструктор, `set*()` всегда `void` и с нормализацией.
- Для пагинации и сортировки применяются унифицированные интерфейсы:
    - `CriteriaWithLimitInterface`
    - `CriteriaWithOffsetInterface`
    - `SortableCriteriaInterface`

> ❗ Сортировка выполняется только по whitelist-полям (`enum SortField`, `enum SortDirection`). Это исключает SQL-инъекции и несогласованность с инфраструктурой.

> ❗ Важно: вся команда должна использовать единую таймзону приложения.

## Зависимости

- В критериях допустимы только: скаляры, Value Object, Enum, DTO, Uuid, DateTimeImmutable (в таймзоне проекта).
- ❗ Запрещено внедрять сервисы, репозитории и инфраструктурные классы.

## Расположение

- Интерфейс и реализации в слое [Domain](../domain.md):

```php
namespace Common\Module\{ModuleName}\Domain\Repository\{EntityName}\{EntityName}CriteriaInterface
namespace Common\Module\{ModuleName}\Domain\Repository\{EntityName}\Criteria\{CriteriaName}Criteria
namespace Common\Module\{ModuleName}\Domain\Repository\{EntityName}\Enum\{EntityName}SortFieldEnum; // при использовании enum
```

- Mapper в слое [Infrastructure](../infrastructure.md):

```php
namespace Common\Module\{ModuleName}\Infrastructure\Repository\{EntityName}\Criteria\Mapper\{CriteriaName}CriteriaMapper
```

## Как используем

- Репозиторий принимает интерфейс критерия, а не реализацию.
- Mapper в инфраструктурном слое преобразует критерий в конкретный запрос (Doctrine QueryBuilder, SQL, Elastic и т.д.).
- ❗ Критерий **не используется напрямую** в бизнес-логике (Domain, Application). Используется только как аргумент
  публичных методов репозитория.
- Маппинг критериев в конкретные запросы — обязанность слоя Infrastructure.

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Domain\Repository\Payment\Criteria;

use Common\Component\Repository\CriteriaWithLimitInterface;
use Common\Component\Repository\CriteriaWithOffsetInterface;
use Common\Component\Repository\SortableCriteriaInterface;
use Common\Component\Repository\Trait\CriteriaWithLimitTrait;
use Common\Component\Repository\Trait\CriteriaWithOffsetTrait;
use Common\Component\Repository\Trait\SortableCriteriaTrait;
use Common\Module\Billing\Domain\Enum\CurrencyEnum;
use Common\Module\Billing\Domain\Repository\Payment\PaymentCriteriaInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class PaymentFindCriteria implements PaymentCriteriaInterface, SortableCriteriaInterface, CriteriaWithLimitInterface, CriteriaWithOffsetInterface
{
    use SortableCriteriaTrait;
    use CriteriaWithLimitTrait;
    use CriteriaWithOffsetTrait;

    public function __construct(
        private ?string $search = null,
        private ?string $email = null,
        private ?string $username = null,
        private ?CurrencyEnum $currency = null,
        private ?Uuid $userUuid = null,
        private ?Uuid $teamAdminUuid = null,
        private ?DateTimeImmutable $from = null,
        private ?DateTimeImmutable $to = null,
    ) {
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): void
    {
        $this->search = $search === null ? null : trim($search);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email === null ? null : mb_strtolower(trim($email));
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username === null ? null : trim($username);
    }

    public function getCurrency(): ?CurrencyEnum
    {
        return $this->currency;
    }

    public function setCurrency(?CurrencyEnum $currency): void
    {
        $this->currency = $currency;
    }

    public function getUserUuid(): ?Uuid
    {
        return $this->userUuid;
    }

    public function setUserUuid(?Uuid $userUuid): void
    {
        $this->userUuid = $userUuid;
    }

    public function getTeamAdminUuid(): ?Uuid
    {
        return $this->teamAdminUuid;
    }

    public function setTeamAdminUuid(?Uuid $teamAdminUuid): void
    {
        $this->teamAdminUuid = $teamAdminUuid;
    }

    public function getFrom(): ?DateTimeImmutable
    {
        return $this->from;
    }

    public function setFrom(?DateTimeImmutable $from): void
    {
        $this->from = $from;
    }

    public function getTo(): ?DateTimeImmutable
    {
        return $this->to;
    }

    public function setTo(?DateTimeImmutable $to): void
    {
        $this->to = $to;
    }
}
```

## Чек лист для проведения ревью кода

- [ ] Интерфейс критерия лежит в `Domain/Repository/{EntityName}`.
- [ ] Реализация находится в `Domain/Repository/{EntityName}/Criteria`.
- [ ] Название соответствует паттерну `{Entity}{Context}Criteria`.
- [ ] В критерии нет бизнес-логики.
- [ ] `set*()` возвращают `void` и нормализуют данные.
- [ ] Пагинация/сортировка реализованы через унифицированные интерфейсы/трейты.
- [ ] Для сортировки применяются enum SortField и SortDirection.
- [ ] Для `limit` и `offset` применяются стандартные трейты (`CriteriaWithLimitTrait`, `CriteriaWithOffsetTrait`).
- [ ] В инфраструктуре есть Mapper, который преобразует критерий в запрос.
