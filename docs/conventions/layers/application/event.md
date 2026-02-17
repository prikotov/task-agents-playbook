# Событие (Event)

**Событие (Event)** — объект, сигнализирующий о факте, произошедшем в системе. События предназначены для информирования
других модулей или частей приложения о факте, произошедшем в текущем модуле. Используются для выполнения реакций (Listeners)
вне транзакций и без ожидания результата.

Используются в составе **Шины событий (Event Bus)**.

## Общие правила

* События **не должны содержать бизнес-логику** — это чистые DTO.
* Все поля события должны быть **readonly и типизированы** (скаляры, VO, DTO, UUID).
* Имя класса события должно быть **с существительным + глагол в прошедшем времени + постфикс `Event`**.
  Пример: `ChatMessageReceivedEvent`, `SmsCreatedEvent`.
* Событие может реализовывать интерфейс, если необходимо объединить обработку нескольких однотипных событий одним
  Listener’ом. Пример: `ChatMessageReceivedEventInterface`.

## Расположение

- В слое [Application](../application.md):

```php
Common\Module\{ModuleName}\Application\Event\{Context?}\{EventName}Event
```

* `{Context?}` — опционально, используется для группировки по смыслу.
* `{EventName}` — имя события без суффикса `Event`.

## Когда использовать

* Когда не нужна транзакционность при выполнении последовательности действий.
* Когда не нужно знать результат обработки кода, подписанного на событие.
* Используются для кросс-модульной реакции на факт, произошедший в модуле.

## Требования к событиям

1. Класс события должен быть **чистым DTO** — не содержать бизнес-логику.
2. Поля события должны быть **readonly** и предоставлять доступ через геттеры.
3. Возможна реализация **интерфейса события** для унификации обработки.

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\Chat\Application\Event\ChatMessage;

use Common\Component\Event\Event;
use Common\Module\Chat\Application\Event\ChatMessageEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ReceivedEvent extends Event implements ChatMessageEventInterface
{
    public function __construct(
        private string $modelUri,
        private Uuid $userUuid,
        private Uuid $projectUuid,
        private Uuid $chatUuid,
        private Uuid $inputChatMessageUuid,
        private Uuid $outputChatMessageUuid,
        private bool $isLocalCacheHit,
        private int $tokensInputCacheHit,
        private int $tokensInputCacheMiss,
        private int $tokensOutput,
        private float $timeToFirstToken,
        private float $generationTime,
    ) {
        parent::__construct();
    }

    public function getModelUri(): string
    {
        return $this->modelUri;
    }

    public function getUserUuid(): Uuid
    {
        return $this->userUuid;
    }

    public function getProjectUuid(): Uuid
    {
        return $this->projectUuid;
    }

    public function getChatUuid(): Uuid
    {
        return $this->chatUuid;
    }

    public function getInputChatMessageUuid(): Uuid
    {
        return $this->inputChatMessageUuid;
    }

    public function getOutputChatMessageUuid(): Uuid
    {
        return $this->outputChatMessageUuid;
    }

    public function isLocalCacheHit(): bool
    {
        return $this->isLocalCacheHit;
    }

    public function getTokensInputCacheHit(): int
    {
        return $this->tokensInputCacheHit;
    }

    public function getTokensInputCacheMiss(): int
    {
        return $this->tokensInputCacheMiss;
    }

    public function getTokensOutput(): int
    {
        return $this->tokensOutput;
    }

    public function getTimeToFirstToken(): float
    {
        return $this->timeToFirstToken;
    }

    public function getGenerationTime(): float
    {
        return $this->generationTime;
    }
}
```

## Чек-лист для код ревью событий

* [ ] Имя класса события соответствует `Существительное + Глагол в прошедшем времени + Event`.
* [ ] Все поля readonly, типизированы.
* [ ] Нет бизнес-логики в событии (только DTO).
* [ ] Для кросс-событий допускается реализация интерфейса.
* [ ] События выбрасываются только своим модулем.
