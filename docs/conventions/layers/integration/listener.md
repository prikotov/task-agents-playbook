# Слушатель (Listener)

**Слушатель (Listener)** — элемент слоя интеграций, подписанный через конфигурацию на конкретное [событие](../application/events.md) и
запускающий реакцию в границах **своего** модуля. Слушатель не зависит от источника события. Если обработка зависит от
инициатора, используйте **Шину команд (Command Bus)**. Применяется в составе **Шины событий (Event Bus)**.

## Общие правила

- **Назначение**: связывает событие с конкретным действием в [Use Case](../application/use_cases.md) своего модуля.
- **Единая точка входа:** публичный `__invoke(Event $event): void`.
- **Именование:** `{Action}On{EventName}Listener`. Пример: `TrackOnReceivedListener`.
- **Границы модуля:** слушатель делегирует только в Use Case своего модуля (напрямую или через компонент шины команд
  вашего приложения).
- Событие и слушатель могут находиться в одном или разных модулях.

## Зависимости

- **Разрешено:** [Use Case](../application/use_cases.md) ([CommandHandler](../application/command_handler.md)/[QueryHandler](../application/query_handler.md))
  **своего** модуля, сервисы интеграций, мапперы/фабрики.
- **Запрещено:**
    - Прямой доступ к БД/HTTP/очередям.
    - Обращения к классам других модулей напрямую (только через сервисы интеграций).

## Расположение

- В слое [Integration](integration.md):

```php
Common\Module\{ModuleName}\Integration\Listener\{Context?}\{Action}On{EventName}Listener
```

`{Context?}` — опционально; используем только если реально нужна группировка (при наличии нескольких слушателей по смежным событиям).
`{Action}` — глагол в повелительной форме (`Track`, `Send`, `Log`, `Notify`).
`{EventName}` — имя доменного события без суффикса "Event"

## Как используем

1. Подписываем слушатель на событие через Symfony Messenger (атрибут или сервис-тег).
2. Внутри `__invoke()` — минимум кода: проверки и делегация в Use Case своего модуля.
3. Кросс-модульные потребности закрываются Use Case через сервисный слой интеграций.

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Integration\Listener\Chat\ChatMessage;

use Common\Application\Component\CommandBus\CommandBusComponentInterface;
use Common\Module\Billing\Application\Enum\UsageTypeEnum;
use Common\Module\Billing\Application\UseCase\Command\Usage\Track\TrackCommand;
use Common\Module\Chat\Application\Event\ChatMessage\ReceivedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class TrackOnReceivedListener
{
    public function __construct(
        private CommandBusComponentInterface $busComponent,
    ) {
    }

    public function __invoke(ReceivedEvent $event): void
    {
        $this->busComponent->execute(new TrackCommand(
            usageType: UsageTypeEnum::chat,
            modelUri: $event->getModelUri(),
            modelUuid: null,
            userUuid: $event->getUserUuid(),
            projectUuid: $event->getProjectUuid(),
            chatUuid: $event->getChatUuid(),
            inputChatMessageUuid: $event->getInputChatMessageUuid(),
            outputChatMessageUuid: $event->getOutputChatMessageUuid(),
            tokensInputCacheHit: $event->getTokensInputCacheHit(),
            tokensInputCacheMiss: $event->getTokensInputCacheMiss(),
            tokensOutput: $event->getTokensOutput(),
            timeToFirstToken: $event->getTimeToFirstToken(),
            generationTime: $event->getGenerationTime(),
        ));
    }
}
```

## Чек-лист для код ревью

- [ ] Имя по схеме `{Action}On{EventName}Listener`, namespace
  `Common\Module\{ModuleName}\Integration\Listener\{Context}`.
- [ ] Только `__invoke()`; минимум кода, только делегирование в Use Case.
- [ ] Нет прямых кросс-модульных вызовов.
- [ ] Корректная регистрация в Symfony Messenger.
