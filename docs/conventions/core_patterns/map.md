# Map-класс (Map)

**Map-класс (Map)** — класс, предназначенный для инкапсуляции логики **сопоставления** значений (Enum, констант) с их представлениями (текст, коды других систем).

## Общие правила

- Разрешены на уровне любого слоя (Domain, Application, Infrastructure, Integration, Presentation).
- Должен содержать только один публичный метод `getAssociationByValue()`, принимающий значение и возвращающее представление.
- Должен содержать один приватный метод, возвращающий ассоциативный массив, где ключ — скалярное значение, а значение — представление.
- Имя приватного метода должно отражать, что именно он возвращает (например, `statusTexts()`, `externalStatuses()`).
- Массив должен содержать соответствия для **всех** значений Enum или набора констант. Фильтрация под конкретный бизнес-сценарий на уровне Map запрещена.
- Может возвращать `string` (текстовое представление) или другой тип (например, enum другой системы).
- При отсутствии ключа в массиве метод `getAssociationByValue()` обязан выбросить `OutOfBoundsException` с указанием значения.
- Запрещена динамическая сборка массива. Данные должны быть жестко заданы (hardcoded) в коде метода.
- Единственная разрешённая зависимость — `Symfony\Component\Translation\TranslatorInterface` для переводов текстовых меток.
- Получаем через DI.

## Зависимости

- Разрешены: примитивы, enum, `Symfony\Component\Translation\TranslatorInterface`.
- Запрещены: БД, HTTP, файлы, очереди, внешние SDK, env/random/time. Класс детерминированный.

## Расположение

### Domain, Application, Infrastructure, Integration

```php
Common\Module\{ModuleName}\{Layer}\Map\{Name}Map
```

Где:
- `{Layer}` — `Domain`, `Application`, `Infrastructure`, `Integration`
- `{Name}Map` — имя мапа (например, `UserStatusTextMap`)

Примеры:
- `Common\Module\User\Domain\Map\UserStatusTextMap`
- `Common\Module\Project\Application\Map\ProjectPriorityTextMap`

### Presentation

```php
{AppName}\Module\{ModuleName}\Map\{Name}Map
```

Примеры:
- `Web\Module\Project\Map\ProjectStatusTextMap`
- `Api\Module\Source\Map\SourceStatusTextMap`

## Как используем

- Внедряем в потребителей через DI.
- Используем для получения текстового представления значений (enum, констант) в UI, логах, уведомлениях.
- Не используем для фильтрации или бизнес-логики — для этого существуют [List-классы](list.md), [Specification](../layers/domain/specification.md), [Query](../layers/application/query_handler.md).
- Запрещено использовать Map напрямую для формирования UI-списков (dropdown). Для этого используйте [Mapper-классы](mapper.md), которые используют Map и List вместе.

## Правила именования

Имя класса должно включать:
1. Название сущности
2. Тип представления (например, `Text`, `Label`, `Code`)
3. Суффикс `Map`

Примеры имён классов:
- `UserStatusTextMap`
- `ProjectPriorityLabelMap`
- `SourceStatusCodeMap`

## Пример

Map-класс для текстового представления статусов проекта (Presentation):

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\Map;

use Symfony\Contracts\Translation\TranslatorInterface;
use Web\Module\Project\Enum\ProjectStatusEnum;
use OutOfBoundsException;

final class ProjectStatusTextMap
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    private function statusTexts(): array
    {
        return [
            ProjectStatusEnum::draft->value => $this->translator->trans('project.status.draft', domain: 'project'),
            ProjectStatusEnum::active->value => $this->translator->trans('project.status.active', domain: 'project'),
            ProjectStatusEnum::paused->value => $this->translator->trans('project.status.paused', domain: 'project'),
            ProjectStatusEnum::completed->value => $this->translator->trans('project.status.completed', domain: 'project'),
            ProjectStatusEnum::archived->value => $this->translator->trans('project.status.archived', domain: 'project'),
        ];
    }

    /**
     * @throws OutOfBoundsException
     */
    public function getAssociationByValue(ProjectStatusEnum $status): string
    {
        return $this->statusTexts()[$status->value]
            ?? throw new OutOfBoundsException("No bounded association for value '{$status->value}'");
    }
}
```

Map-класс для сопоставления enum'ов разных систем (Domain):

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Domain\Map;

use Common\Module\Billing\Domain\Enum\PaymentStatusEnum;
use External\Payment\Enum\ExternalPaymentStatusEnum;
use OutOfBoundsException;

final class PaymentStatusMap
{
    private function externalStatuses(): array
    {
        return [
            PaymentStatusEnum::pending->value => ExternalPaymentStatusEnum::processing->value,
            PaymentStatusEnum::paid->value => ExternalPaymentStatusEnum::completed->value,
            PaymentStatusEnum::failed->value => ExternalPaymentStatusEnum::rejected->value,
            PaymentStatusEnum::refunded->value => ExternalPaymentStatusEnum::refunded->value,
        ];
    }

    /**
     * @throws OutOfBoundsException
     */
    public function getAssociationByValue(PaymentStatusEnum $status): string
    {
        return $this->externalStatuses()[$status->value]
            ?? throw new OutOfBoundsException("No bounded association for value '{$status->value}'");
    }
}
```

## Использование с Mapper для dropdown-списков

Для составления массивов для dropdown используйте [Mapper-классы](mapper.md), которые комбинируют Map и List:

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\Mapper;

use Web\Module\Project\Enum\ProjectStatusEnum;
use Web\Module\Project\List\ProjectStatusList;
use Web\Module\Project\Map\ProjectStatusTextMap;

final class ProjectStatusTextMapper
{
    public function __construct(
        private readonly ProjectStatusTextMap $statusTextMap,
        private readonly ProjectStatusList $statusList
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function map(): array
    {
        $statuses = $this->statusList->all();

        return array_combine(
            array_map(
                fn(ProjectStatusEnum $status) => $status->value,
                $statuses,
            ),
            array_map(
                fn(ProjectStatusEnum $status) => $this->statusTextMap->getAssociationByValue($status),
                $statuses,
            ),
        );
    }
}
```

## Чек-лист код-ревью

- [ ] Класс `final`, без состояния кроме зависимости от TranslatorInterface.
- [ ] Содержит только один публичный метод `getAssociationByValue()`.
- [ ] Содержит один приватный метод, возвращающий полный массив ассоциаций.
- [ ] Массив статичен, без логики составления на основе контекста.
- [ ] При отсутствии значения выбрасывается `OutOfBoundsException` с указанием значения.
- [ ] Единственная зависимость — `TranslatorInterface` (если нужны переводы).
- [ ] Нет обращений к БД, HTTP, файлам и другим внешним ресурсам.
- [ ] Namespace соответствует шаблону.
- [ ] Имя класса соответствует правилам именования.