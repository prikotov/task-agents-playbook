# Архитектура модуля Notification (Уведомления)

## 1. Мотивация
Модуль `Notification` инкапсулирует бизнес-логику отправки уведомлений пользователю через различные каналы (UI/Turbo/Mercure, Email, SMS и др.). Это позволяет избежать размытия логики уведомлений по другим модулям, обеспечивая единый интерфейс и правила для управления сообщениями пользователю.

## 2. Границы ответственности модуля
Модуль `Notification` отвечает за:
- Прием запросов на уведомление через Use Cases или слушателей событий.
- Управление шаблонами и контентом уведомлений.
- Выбор каналов доставки (UI, Email, Push).
- Техническую реализацию доставки через различные провайдеры (Mercure, SMTP-клиенты и др.).
- Отслеживание жизненного цикла и статуса уведомлений.

## 3. Целевая архитектура

### Domain Layer
- **Notification**: Сущность уведомления.
- **NotificationChannelEnum**: Перечисление поддерживаемых каналов.
- **NotificationRepositoryInterface**: Интерфейс для хранения и поиска уведомлений.
- **BroadcasterInterface**: Интерфейсы для рассылки сообщений в реальном времени.

### Application Layer
- **UseCase/Command/TriggerNotification**: Команды для инициации процесса уведомления.
- **UseCase/Query/FindActive**: Получение активных уведомлений пользователя.

### Infrastructure Layer
- **Service/Broadcaster**: Технические реализации интерфейсов рассылки (например, через Mercure).
- **Repository/NotificationRepository**: Реализация репозитория через Doctrine.

### Integration Layer
- **Listener**: Слушатели событий других модулей, инициирующие уведомления.
- **Service**: Сервисы для прямой интеграции других модулей с системой уведомлений.

## 4. Контракт интеграции
Взаимодействие с модулем `Notification` осуществляется двумя способами:
1. **Событийная модель (рекомендуемая)**: Модули генерируют доменные события, на которые подписывается `Notification`.
2. **Прямой вызов**: Использование Use Cases или сервисов интеграции модуля `Notification`.

## 5. Принципы реализации
- **Слабая связность**: Модули-источники событий ничего не знают о способах и факте доставки уведомлений.
- **Расширяемость**: Добавление новых каналов доставки не должно требовать изменения бизнес-логики модулей-источников.
- **Изоляция**: Все технические детали работы с внешними сервисами рассылки скрыты внутри слоя Infrastructure модуля `Notification`.

## 6. Как пользоваться нотификациями

### 6.1 Web-уведомления (toast)

1. В модуле-источнике формируем уведомление через Application-слой:
   - используем `TriggerCommand` и `CommandBusComponentInterface`;
   - `NotificationChannelEnum::webToast` — канал по умолчанию;
   - `fingerprint` — для дедупликации и переиспользования уведомления;
   - `requiresAcknowledgement` — включаем, если ожидается подтверждение.
2. В Web UI конфигурацию потока формирует `notification_stream_config(app.user)`:
   - topic, publicUrl, listUrl, ackUrlTemplate, csrfToken.
3. Клиентский слой подписывается на Mercure:
   - `apps/web/assets/controllers/notification-toast_controller.js` использует EventSource с `withCredentials`;
   - при ошибках использует fallback `NotificationRoute::LIST`.
4. Подтверждение:
   - POST в `NotificationRoute::ACK` с `X-CSRF-TOKEN` (token id: `notification_ack`);
   - обработка идёт через `AcknowledgeCommand`.

### 6.2 Live-обновления статуса Source (Turbo Streams)

1. Источник события: `SourceStatusChangedEvent` из пайплайна Source.
2. Интеграция:
   - `BroadcastOnSourceStatusChangedListener` делает async dispatch `BroadcastStatusCommand`;
   - routing в Messenger: `source_status_live_updates`.
3. Worker-side delivery:
   - `BroadcastStatusCommandHandler` обрабатывает сообщения из `source_status_live_updates`;
   - применяется ordering/idempotency guard для stale/duplicate событий;
   - `RenderSourceStatusTurboStreamService` формирует shared payload без зависимости от `@web.notification/*`.
4. Публикация:
   - `SourceStatusBroadcasterInterface` отправляет payload в каноничный topic `rtrim(SOURCE_STATUS_TOPIC_BASE_URI, '/') . '/{userUuid}'`.
5. Payload contract:
   - всегда `source-status-badge-{sourceUuid}`,
   - флаг `SOURCE_STATUS_LIVE_DEGRADATION_UI` управляет режимами:
     - `off`: только badge stream,
     - `limited`: badge + `source-live-health-*` + `source-status-meta-*`,
     - `on`: badge + `source-live-health-*`.
6. Подписка в UI:
   - `Web\EventSubscriber\MercureAuthorizationSubscriber` регистрирует source-status topic в `TurboStreamTopicRegistry`;
   - в `apps/web/templates/base.html.twig` используется единый `<turbo-stream-source>` через `turbo_stream_listen(turbo_stream_topics(), ...)`.

Источник истины по контракту: `docs/architecture/module/source-status-live-updates-worker-web.md`.

### 6.3 Почему turbo_stream_listen не используется для toast-уведомлений

- Web toast уведомления получают JSON payload и рендерятся через JS (`notification-toast_controller.js`).
- Turbo Streams применяется, когда backend публикует HTML `<turbo-stream>` и нужно автоматическое обновление DOM.
- Для toast-уведомлений UI строится вручную (Bootstrap Toast), поэтому используется прямой EventSource, а не `turbo_stream_listen()`.

## 7. Основные сущности и точки расширения

### Domain
- `NotificationModel`, `NotificationChannelEnum`, `NotificationSeverityEnum`, `NotificationStatusEnum`
- `NotificationRepositoryInterface`
- `SourceStatusBroadcasterInterface`

### Application
- `TriggerCommand` / `TriggerCommandHandler`
- `AcknowledgeCommand` / `AcknowledgeCommandHandler`
- `FindActiveQuery` / `FindActiveQueryHandler`
- `BroadcastStatusCommand` / `BroadcastStatusCommandHandler`
- `NotificationDto`, `NotificationMapper`
- `NotificationTopicFactory`
- `SourceStatusTopicFactory`

### Infrastructure / Integration
- `MercureNotificationBroadcaster`, `MercureSourceStatusBroadcaster`
- `NullNotificationBroadcaster`, `NullSourceStatusBroadcaster`
- `BroadcastOnSourceStatusChangedListener`
- `RenderSourceStatusTurboStreamService`

### Presentation (Web)
- `Web\Module\Notification\Controller\Notification\ListController`
- `Web\Module\Notification\Controller\Notification\AcknowledgeController`
- `Web\Module\Notification\Route\NotificationRoute`
- `Web\Component\Twig\Extension\NotificationExtension`
- `Web\Component\Notification\NotificationStreamConfigProvider`
- `apps/web/assets/controllers/notification-toast_controller.js`
- `apps/web/src/Module/Notification/Resource/templates/source/status_turbo_stream.html.twig`

## 8. Symfony references

- Mercure: https://symfony.com/doc/current/mercure.html
- UX Turbo: https://symfony.com/doc/current/ux/turbo.html
- Messenger: https://symfony.com/doc/current/messenger.html
- Security (auth/CSRF): https://symfony.com/doc/current/security.html
- Twig Templates: https://symfony.com/doc/current/templates.html

## 9. Related Architecture Decisions

- `docs/architecture/module/source-status-live-updates-worker-web.md` — target worker -> web delivery architecture for split-node live updates.
