# Объект передачи данных (Data Transfer Object, DTO)

**Объект передачи данных (Data Transfer Object, DTO)** — простой неизменяемый объект, предназначенный для
переноса структурированных данных между слоями и границами системы без бизнес‑логики.
Основная цель — обеспечить строгую типизацию входов/выходов и изоляцию доменных сущностей от внешних интерфейсов.

## Общие правила

- Только данные: без бизнес‑логики, побочных эффектов и инфраструктурных вызовов.
- Неизменяемость: `final readonly class` с промотированными свойствами конструктора; наследование запрещено (`final`).
- Строгая типизация: скаляры, `Enum`, `UuidInterface/Uuid`, `DateTimeImmutable`, другие DTO. Использование `VO` —
  только если VO относится к тому же слою и DTO не пересекает границы слоя (например, внутренние DTO Domain). Публичные
  DTO для Presentation/Integration/Application, используемые на границах слоёв, не должны включать доменные VO — их
  следует маппить в примитивы/ENUM/простые вложенные DTO.
- Коллекции — только типизированные: описываем через PHPDoc `@var` (например, `@var ProjectDto[]`).
- Никаких сервисов внутри DTO (ни через DI, ни через фабрики). Никаких дополнительных методов — только конструктор и публичные свойства.
- Конструктор не содержит логики и преобразований: значения принимаются «как есть».
- Никаких выбросов исключений, валидации или обращений к окружению. Валидация — во входных точках (формы/Presentation) или на уровне Application.
- Именование: суффикс `Dto`. Контекст — в имени/namespace (`RequestDto`, `ResponseDto`, `ResultDto`, `...Dto`).

## Зависимости

- Допустимо:
  - скаляры (`int`, `string`, `float`, `bool`), `numeric-string` через PHPDoc;
  - `Enum`, `UuidInterface/Uuid`, `DateTimeImmutable`;
  - Value Object (VO) — только для внутрислойных DTO (например, внутри Domain); в публичных DTO VO заменяем на
    примитивы/Enum/вложенные DTO и выполняем маппинг на границе слоя.
  - другие DTO того же модуля/контекста.
- Запрещено:
  - сервисы (интерфейсы и реализации), репозитории, компоненты;
  - обращения к БД/HTTP/файлам/очередям;
  - зависимости на чужие слои, если нарушается модульная изоляция.

## Расположение

- Общие DTO приложения:

```
Common\Application\Dto\{Name}Dto
```

- DTO уровня модуля (Application):

```
Common\Module\{ModuleName}\Application\Dto\{Name}Dto
```

- DTO, связанные с конкретным Use Case (запрос/ответ):

```
Common\Module\{ModuleName}\Application\UseCase\{Command|Query}\{Case}\{Request|Result|Response}Dto
```

- DTO для компонентов/интеграций:

```
Common\Module\{ModuleName}\Integration\Component\{Component}\Dto\{Name}Dto
Common\Module\{ModuleName}\Infrastructure\Component\{Component}\Dto\{Name}Dto
```

- DTO доменного уровня (когда доменная операция возвращает структурированные данные):

```
Common\Module\{ModuleName}\Domain\Dto\{Name}Dto
```

## Как используем

- В Presentation формируем входные данные в `RequestDto` и получаем `Response/ResultDto`.
- В Application `Use Case` принимает/возвращает DTO, маппит из/в доменные модели через мапперы.
- В Integration/Infrastructure компоненты принимают/возвращают DTO. Сетевые ответы маппим в DTO через `Mapper`.
- DTO не «знают» о слоях и не тянут зависимости — это переносимые структуры данных.
- Для денежных и точных величин используем `numeric-string` или VO; числа с плавающей точкой не применяем для денег и курсов.

## Когда используем

- Для передачи данных между слоями и границами подсистем.
- В Application разрешено общее DTO, если один и тот же набор данных переиспользуется в нескольких Use Case.
- В Domain используем DTO, когда нужно принять/вернуть данные конкретного сервиса. Если DTO начинает «гулять» по коду, рассмотрите перенос в Value Object (VO).

## Пример

Общий DTO пагинации уровня приложения:

```php
<?php

declare(strict_types=1);

namespace Common\Application\Dto;

final readonly class PaginationDto
{
    public function __construct(
        public int $limit,
        public int $offset = 0,
    ) {
    }
}
```

DTO результата выборки проекта с вложенным DTO автора (модуль `Project`):

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Application\Dto;

use Common\Module\Project\Application\Enum\ProjectStatusEnum;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class ProjectDto
{
    public function __construct(
        public int $id,
        public Uuid $uuid,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $lastUsedAt,
        public ProjectStatusEnum $status,
        public DateTimeImmutable $statusUpdatedAt,
        public string $title,
        public string $description,
        public ?CreatorDto $creator,
        public bool $isPublished,
        public int $sourcesCount,
        public int $chatsCount,
        public int $artifactsSize,
        public int $documentsSize,
        public int $documentChunksSize,
        public int $diskSize,
        /** @var array<string, numeric-string> */
        public array $usageCostTotals,
    ) {
    }
}
```

DTO запроса к внешнему компоненту (пример для Integration Component):

```php
<?php

declare(strict_types=1);

namespace Common\Module\SpeechToText\Infrastructure\Component\WhisperCppServer\Dto;

use Common\Module\SpeechToText\Infrastructure\Component\WhisperCppServer\Enum\ResponseFormatEnum;

final readonly class InferenceRequestDto
{
    public function __construct(
        public string $file,
        public ResponseFormatEnum $responseFormat,
        public ?string $prompt = null,
    ) {
    }
}
```

## Чек-лист код‑ревью

- [ ] Класс `final readonly`, свойства промотированы и строго типизированы.
- [ ] Нет зависимостей на сервисы/репозитории/компоненты и внешнее окружение.
- [ ] Коллекции типизированы через PHPDoc (`@var FooDto[]`).
- [ ] Название и namespace отражают контекст использования (`Request/Response/Result` при необходимости).
- [ ] Денежные/точные величины представлены `numeric-string` или VO.
- [ ] DTO используется на границах слоя, а не подменяет доменные сущности.
