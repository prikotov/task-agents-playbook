# Сервис (Service)

**Сервис (Service)** — класс, реализующий операции, относящиеся к бизнес-логике, инфраструктуре или интеграции, не
подходящие по смыслу для размещения в сущностях или значениях-объектах. Сервисы реализуют операции, не сохраняющие
внутреннее состояние между вызовами (stateless), и обеспечивают выполнение определённого бизнес-процесса или технической
задачи.

## Общие правила

- Всегда **без состояния** (stateless).
- Всегда определён **интерфейс** и **реализация**.
- Методы сервиса должны быть **чётко именованы** и отражать выполняемое действие.
- Допустимо наличие **нескольких методов** в одном сервисе, если они логически связаны.
- Название сервиса — глагол + предмет + `Service` (например, `ChangeStatusService`).
- Внедрение через DI (constructor injection). Подробности: [Symfony Service Container](https://symfony.com/doc/current/service_container.html).
- Для всех сервисов, внедряемых по интерфейсу, обязательно задаём явный alias
  `Interface -> Implementation` в `services.yaml`, независимо от настроек
  autowire. Подробности: [Working with Interfaces](https://symfony.com/doc/current/service_container/interfaces.html).

## Зависимости

- Зависимости определяются по типу сервиса (см. ниже).
- Зависимости передаются через конструктор.
- ❗ Запрещено внедрение реализаций — только интерфейсы.
- Сервисы **не могут зависеть от слоёв ниже себя**.

---

# Доменный сервис (Domain Service)

**Доменный сервис (Domain Service)** — класс, реализующий высокоуровневую бизнес-логику, не принадлежащую ни одной из
сущностей.

## Общие правила

- Используется только внутри слоя **Domain**.
- Работает только с объектами домена: `Entity`, `VO`, `DTO`, `Enum`, `UuidInterface`, примитивы.
- Может зависеть от:
    - других сервисов своего модуля;
    - `Specification`;
    - `Calculator`;
    - репозиториев;
    - общих компонентов.
- ❗ **Запрещено** зависеть от внешней инфраструктуры.

## Расположение

- Интерфейс и реализация в `Domain`:

```php
Common\Module\{ModuleName}\Domain\Service\{Context?}\{ServiceName}ServiceInterface
Common\Module\{ModuleName}\Domain\Service\{Context?}\{ServiceName}Service
```

## Как используем

- Вызывается внутри Use Case или другого доменного сервиса.
- Передаётся через DI в Use Case.

---

# Инфраструктурный сервис (Infrastructure Service)

**Инфраструктурный сервис (Infrastructure Service)** — реализация логики, связанной с технической стороной работы
приложения, например, работа с системой логирования, кешем, файловой системой и т.п.

## Общие правила

- Интерфейс размещается в `Domain`, реализация — в `Infrastructure`.
- Работает с DTO, VO, Enum, `UuidInterface`, примитивами.
- Зависит только от компонентов или инфраструктурных интерфейсов.
- ❗ **Не имеет доступа к доменному коду**, кроме интерфейсов.
- Даже при включённом `autowire` в `services.yaml` всегда добавляй явный alias
  `Interface -> Implementation`, чтобы контейнер однозначно резолвил сервис и
  не зависел от эвристики "нашёлся единственный класс". Подробности: [Working with Interfaces](https://symfony.com/doc/current/service_container/interfaces.html).

## Расположение

- Интерфейс:

```php
Common\Module\{ModuleName}\Domain\Service\{Context?}\{ServiceName}ServiceInterface
```

- Реализация:

```php
Common\Module\{ModuleName}\Infrastructure\Service\{Context?}\{ServiceName}Service
```

## Как используем

- Внедряется в Use Case или доменные сервисы (если интерфейс расположен в Domain).
- Может использовать Symfony сервисы, PSR-совместимые библиотеки.

---

# Application сервис (Application Service)

**Application сервис (Application Service)** — класс, реализующий оркестрацию бизнес-операций на уровне приложения. Он координирует выполнение сценариев использования, объединяя несколько операций домена в единый процесс, но не содержит бизнес-логики.

## Общие правила

- Интерфейс и реализация в `Application`.
- Работает с DTO, VO, Enum, доменными сущностями.
- Может зависеть от:
    - сервисов своего модуля (Domain и Application);
    - репозиториев (через интерфейсы из Domain);
    - компонентов Infrastructure;
    - общих компонентов (PersistenceManagerInterface, EventBusInterface).
- ❗ **Запрещено** содержать бизнес-логику — только оркестрация.
- ❗ **Запрещено** зависеть от Infrastructure или Integration слоёв напрямую.
- Используется только внутри слоя Application или вызывается из Use Cases.

## Расположение

- Интерфейс и реализация в `Application`:

```php
Common\Module\{ModuleName}\Application\Service\{Context?}\{ServiceName}ServiceInterface
Common\Module\{ModuleName}\Application\Service\{Context?}\{ServiceName}Service
```

## Как используем

- Вызывается внутри Use Case (Command/Query Handler) или других Application сервисов.
- Внедряется через DI в Use Case.
- Выполняет сложную оркестрацию, которая не укладывается в один Use Case.

---

# Интеграционный сервис (Integration Service)

**Интеграционный сервис (Integration Service)** — класс, обеспечивающий связь между доменом и внешними API,
микросервисами и другими системами.

## Общие правила

- Интерфейс в `Domain`, реализация в `Integration`.
- Возвращает и принимает: DTO, VO, Enum, примитивы, `UuidInterface`.
- ❗ Не зависит от **реализаций** домена. Допускается только внедрение интерфейсов, определённых в доменном слое.

## Расположение

- Интерфейс:

```php
Common\Module\{ModuleName}\Domain\Service\{Context?}\{ServiceName}ServiceInterface
```

- Реализация:

```php
Common\Module\{ModuleName}\Integration\Service\{Context?}\{ServiceName}Service
```

## Как используем

- Внедряется через DI в Use Case, доменные или инфраструктурные сервисы.

---

# Правила именования

`{ServiceName}` = `{Action}` + `{Target}`

| Элемент   | Правило                            | Пример                            |
|-----------|------------------------------------|-----------------------------------|
| Класс     | `{Action}{Target}Service`          | ChangeStatusService               |
| Интерфейс | `{Action}{Target}ServiceInterface` | ChangeStatusServiceInterface      |
| Метод     | Глагол в названии                  | `change()`, `send()`, `getById()` |

- `Action` — глагол, обозначающий операцию (Send, Change, Create).
- `Target` — предмет действия (Email, Status, Payment).

# Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Domain\Service\FxRate;

use Common\Module\Billing\Domain\Service\FxRate\FxRateDto;

interface FetchFxRateServiceInterface
{
    /**
    * @return FxRateDto[]
    */
    public function fetch(): array;
}
```

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Integration\Service\FxRate\ExchangeRateApi;

use Common\Module\Billing\Domain\Enum\CurrencyEnum;
use Common\Module\Billing\Domain\Service\FxRate\FxRateDto;
use Common\Module\Billing\Domain\Service\FxRate\FetchFxRateServiceInterface;
use Common\Module\Billing\Integration\Component\ExchangeRateApi\ExchangeRateApiComponentInterface;
use Override;

final readonly class FetchFxRateService implements FetchFxRateServiceInterface
{
    public function __construct(
        private ExchangeRateApiComponentInterface $component
    ) {
    }

    #[Override]
    public function fetch(): array
    {
        $response = $this->component->getLatestRates('USD');

        $rateValue = 0.0;
        foreach ($response->rates as $rateDto) {
            if ($rateDto->charCode === 'CNY') {
                $rateValue = (float) $rateDto->rate;
                break;
            }
        }

        /** @var numeric-string $rate */
        $rate = number_format($rateValue, 10, '.', '');

        return [
            new FxRateDto(
                baseCurrency: CurrencyEnum::usd,
                quoteCurrency: CurrencyEnum::cny,
                rate: $rate,
                asOf: $response->asOf,
            )
        ];
    }
}
```
