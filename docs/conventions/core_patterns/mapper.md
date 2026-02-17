# Маппер (Mapper)

**Маппер (Mapper)** — класс для технического преобразования структуры данных (shape mapping) на границах слоёв/модулей и интеграций. Бизнес-логику не реализует.

## Общие правила

- В названии класса указывается постфикс `Mapper`.
- Stateless (без состояния), кэширование запрещено.
- Принимает типизированные данные (DTO/VO/Entity/Criteria). Массивы — только в слоях Integration/Infrastructure и только как контракт внешнего формата.
- Массивы описываются shape-типами (`array{...}`) или заменяются DTO при повторном использовании.
- Методы всегда возвращают значение. `void` запрещён.
- Возвращает новый объект или массив. Изменение входного объекта допускается только для мутабельных объектов и должно быть отражено в названии метода: `apply*()`, `fill*()`.
- Имена методов отражают направление: `mapTo*()`, `mapFrom*()`.
- Метод `map()` допускается, если направление однозначно из контекста класса.
- Не изменяет состояние БД и не выполняет I/O.
- Получается из DI-контейнера. В тестах допускается ручное создание.
- Может выбрасывать только [исключения](exception.md) своего слоя или общие. Исключения сторонних библиотек не пробрасываются наружу.

## Зависимости

**Разрешено**:
- Через DI зависимости из того же слоя.
- Примитивы, Enum, VO своего модуля и своего слоя.
- Другие мапперы того же слоя.

**Запрещено**:
- Зависимости от более внешних слоёв (например, Domain → Application / Infrastructure / Presentation).
- Любое взаимодействие с БД.
- Внешние HTTP-клиенты, SDK, API-вызовы.
- Глобальное состояние, статические синглтоны.
- Логика бизнес-процессов.

## Расположение

Маппер размещается в слое, где он используется. Примеры:

**Application слой** — маппинг Domain ↔ DTO:

```
Common\Module\{ModuleName}\Application\Mapper\{Name}Mapper
```

**Infrastructure слой** — маппинг Criteria → QueryBuilder, DTO → массив:

```
Common\Module\{ModuleName}\Infrastructure\Repository\{Entity}\Criteria\Mapper\{Name}Mapper
Common\Module\{ModuleName}\Infrastructure\Component\{Component}\Mapper\{Name}Mapper
Common\Module\{ModuleName}\Infrastructure\Mapper\{Name}Mapper
```

**Domain слой** — маппинг внутри домена:

```
Common\Module\{ModuleName}\Domain\Mapper\{Name}Mapper
```

**Integration слой** — маппинг для внешних API:

```
Common\Module\{ModuleName}\Integration\Component\{Component}\Mapper\{Name}Mapper
```

**Presentation слой** — маппинг для контроллеров:

```
Common\Module\{ModuleName}\Presentation\Mapper\{Name}Mapper
```

## Как используем

- Маппер используется только на границах слоёв/модулей и интеграций.
- Маппер используется для адаптации контрактов между слоями/модулями и внешними форматами.
- Маппер не должен использоваться для замены бизнес-логики.
- Методы маппера вызываются через экземпляр, полученный из DI.
- Маппер должен быть `final` и `readonly`.

## Пример

### Пример 1: Маппинг Domain → DTO (Application слой)

```php
<?php

declare(strict_types=1);

namespace Common\Module\Source\Application\Mapper;

use Common\Module\Source\Application\Dto\CreatorDto;
use Common\Module\Source\Application\Dto\SourceDto;
use Common\Module\Source\Application\Enum\SourceDiarizationModeEnum;
use Common\Module\Source\Application\Enum\SourceLanguageEnum;
use Common\Module\Source\Domain\Entity\SourceModel;

final readonly class SourceDtoMapper
{
    public function __construct(
        private DomainToApplicationSourceStatusMapper $sourceStatusMapper
    ) {
    }

    public function map(SourceModel $source): SourceDto
    {
        $creator = $source->getCreator();
        $diskSize = $source->getArtifactsSize()
            + $source->getDocumentsSize()
            + $source->getDocumentChunksSize();

        return new SourceDto(
            $source->getId(),
            $this->sourceStatusMapper->map($source->getStatus()),
            $source->getUri(),
            $source->getTitle(),
            SourceDiarizationModeEnum::from($source->getDiarizationMode()->value),
            SourceLanguageEnum::from($source->getLanguage()->value),
            $source->getDescription(),
            $source->getFilename(),
            $creator !== null ? new CreatorDto(
                $creator->getUuid(),
                $creator->getUsername(),
            ) : null,
            $source->getDuration(),
            $source->getArtifactsSize(),
            $source->getDocumentsSize(),
            $source->getDocumentChunksSize(),
            $diskSize,
        );
    }
}
```

### Пример 2: Маппинг DTO → массив (Integration слой)

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Integration\Component\TBusiness\Mapper;

use Common\Module\Billing\Integration\Component\TBusiness\Dto\InitRequestDto;
use Common\Module\Billing\Integration\Component\TBusiness\Helper\TokenHelper;

final readonly class InitRequestMapper
{
    public function __construct(
        private TokenHelper $tokenHelper
    ) {
    }

    /**
     * @return array{TerminalKey: string, Amount: int, OrderId: string, Description?: string, Recurrent?: string, PayType?: string, Token: string}
     */
    public function map(InitRequestDto $dto, string $terminalKey, string $secretKey): array
    {
        $payload = [
            'TerminalKey' => $terminalKey,
            'Amount' => $dto->amount,
            'OrderId' => $dto->orderId,
        ];

        if ($dto->description !== null) {
            $payload['Description'] = $dto->description;
        }
        if ($dto->recurrent !== null) {
            $payload['Recurrent'] = $dto->recurrent;
        }
        if ($dto->payType !== null) {
            $payload['PayType'] = $dto->payType->value;
        }

        $payload['Token'] = $this->tokenHelper->build($payload, $secretKey);

        return $payload;
    }
}
```

## Чек-лист для ревью кода

- [ ] Класс имеет постфикс `Mapper` и находится в правильном слое.
- [ ] Класс помечен как `final` и `readonly`.
- [ ] Методы возвращают типизированные объекты (DTO, массив, модель), не `void`.
- [ ] Если вход/выход — массив, shape-тип описан (array{...}), mixed не используется.
- [ ] Нет изменений состояния БД и I/O-операций внутри маппера.
- [ ] Зависимости внедряются через конструктор, нет глобального состояния.
- [ ] Логика преобразования отделена от бизнес-логики домена.
- [ ] Зависимости соответствуют правилам слоя.
- [ ] Unit-тесты обязательны при наличии ветвлений, вариантов маппинга и исключений.
- [ ] Нет неявной мутабельности входных объектов.
- [ ] Исключения библиотек не пробрасываются: оборачиваются в исключения слоя/общие
