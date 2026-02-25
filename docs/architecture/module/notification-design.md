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
   - в `apps/web/templates/base.html.twig` используется единый скрытый элемент (например, `<div class="d-none" ...>`) с атрибутами из `turbo_stream_listen(turbo_stream_topics(), ...)`.

Источник истины по контракту: `docs/architecture/module/source-status-live-updates-worker-web.md`.

### 6.3 Почему turbo_stream_listen не используется для toast-уведомлений

- Web toast уведомления получают JSON payload и рендерятся через JS (`notification-toast_controller.js`).
- Turbo Streams применяется, когда backend публикует HTML `<turbo-stream>` и нужно автоматическое обновление DOM.
- Для toast-уведомлений UI строится вручную (Bootstrap Toast), поэтому используется прямой EventSource, а не `turbo_stream_listen()`.

### 6.4 Важная тонкость Turbo 8 для SSE-подписки

- Не использовать `<turbo-stream-source {{ turbo_stream_listen(...) }}>` как носитель атрибутов Stimulus/Mercure.
- Причина: Web Component `turbo-stream-source` из Turbo (`@hotwired/turbo`) открывает `EventSource(this.src)` и не использует `data-controller`.
- Если у элемента нет `src`, браузер может открыть SSE на текущую страницу (`/invitations`, `/sources`, ...), что приводит к ошибке:
  `EventSource's response has a MIME type ("text/html") that is not "text/event-stream"`.
- Безопасный паттерн:
  использовать обычный элемент (`div`/`span`) с атрибутами `turbo_stream_listen(...)`, чтобы подписку создавал контроллер `symfony/ux-turbo/mercure-turbo-stream`.

### 6.5 Postmortem: `ERR_NETWORK_CHANGED` на `/invitations` (Mercure SSE)

- Симптом:
  в браузере периодически появлялось `GET /.well-known/mercure?... net::ERR_NETWORK_CHANGED 200 (OK)` для topic-ов `notifications/users/{uuid}` и `source-status/users/{uuid}`.
- Диагностика по контейнерам:
  `traefik` / `nginx` / `mercure` стабильно отдавали `200` с `text/event-stream`, но в `mercure` наблюдался цикл `Subscriber disconnected -> New subscriber` (клиентский reconnect).
- Причина:
  проблема была на стыке long-lived SSE и proxy-транспорта (Traefik -> Nginx -> Mercure): переключения/особенности transport layer и idle-поведение давали разрывы потока без серверной ошибки.

Обязательные настройки (не откатывать):

- `devops/nginx/conf.d/dev/task.conf` и `devops/nginx/conf.d/e2e/task.conf` для `location ^~ /.well-known/mercure`:
  `proxy_http_version 1.1`, `proxy_set_header Connection ""`, `proxy_buffering off`, `proxy_request_buffering off`, `proxy_cache off`, `chunked_transfer_encoding off`, `proxy_read_timeout 1h`, `proxy_send_timeout 1h`, `add_header X-Accel-Buffering no`.
- Там же:
  `proxy_hide_header Alt-Svc;` и `add_header Alt-Svc "clear" always;` (не пробрасывать upstream hints браузеру для этого SSE path).
- `compose.yaml` и `compose.e2e.yaml` (`mercure.environment.MERCURE_EXTRA_DIRECTIVES`):
  `heartbeat 15s` и `write_timeout 0s`.
- `devops/traefik/dynamic.yml`:
  `tls.options.default.alpnProtocols: [http/1.1]` для dev TLS entrypoint, чтобы убрать нестабильный SSE over HTTP/2 в локальном контуре.

Проверка после изменений:

- Header check:
  `/.well-known/mercure?...` должен возвращать `Content-Type: text/event-stream` и `Alt-Svc: clear`.
- Runtime check:
  в `make logs-traefik`, `make logs-nginx`, `make logs-mercure` не должно быть 4xx/5xx по SSE path, а длительность соединений должна быть длительной и с корректным reconnect без консольных ошибок.
- E2E check:
  `make tests-e2e-invitations-mercure-stability DURATION_SEC=...`.

### 6.6 Production Runbook: настройка и проверка Mercure

Цель: стабильный long-lived SSE без `MIME text/html` и без массовых `ERR_NETWORK_CHANGED`.

#### 6.6.1 Базовая схема в production

- Web app и Mercure должны быть доступны через один публичный origin (рекомендуемо):
  `https://<app-domain>/.well-known/mercure`.
- Внешний трафик: `Client -> Edge proxy (Traefik/Ingress/Nginx) -> Nginx app -> Mercure hub`.
- Все прокси на SSE path должны работать в passthrough-режиме потока, без буферизации.

#### 6.6.2 Обязательные настройки proxy для `/.well-known/mercure`

Для каждого proxy-hop (edge и внутренний Nginx), где это применимо:

- `proxy_http_version 1.1`
- `proxy_set_header Connection ""`
- `proxy_buffering off`
- `proxy_request_buffering off`
- `proxy_cache off`
- `chunked_transfer_encoding off`
- `proxy_read_timeout 1h` (или выше)
- `proxy_send_timeout 1h` (или выше)
- `add_header X-Accel-Buffering no`
- Не пробрасывать Alt-Svc от upstream для SSE path:
  `proxy_hide_header Alt-Svc;`
  `add_header Alt-Svc "clear" always;`

Если в контуре наблюдаются нестабильности SSE over HTTP/2, зафиксировать ALPN до HTTP/1.1 на входном TLS-роуте для app domain.

#### 6.6.3 Обязательные настройки Mercure hub

- `heartbeat 15s` (регулярный keepalive по потоку)
- `write_timeout 0s` (не обрывать long-lived стрим по write timeout)
- Корректные `publisher/subscriber` JWT key pair.
- `CORS` и `publish_origins` только для доверенных origin.

#### 6.6.4 Cookie/JWT авторизация подписчика

- Cookie `mercureAuthorization` должна быть:
  - `Path=/.well-known/mercure`
  - `Secure`
  - `HttpOnly`
  - `SameSite` в соответствии с моделью доменов (для same-origin обычно `Strict`/`Lax`).
- В браузере запрос к `/.well-known/mercure` должен уходить с этой cookie.

#### 6.6.5 Проверка после deploy (smoke + stability)

1. Проверить заголовки SSE endpoint:
   `curl -k --http1.1 -I 'https://<app-domain>/.well-known/mercure?topic=<urlencoded-topic>'`
   Ожидаемо: `200`, `Content-Type: text/event-stream`, `Alt-Svc: clear`.
2. Проверить, что endpoint не возвращает HTML:
   `curl -k --http1.1 -N 'https://<app-domain>/.well-known/mercure?topic=<urlencoded-topic>'`
   Поток должен оставаться открытым и не содержать HTML-страницу приложения.
3. В браузере (DevTools -> Network -> `mercure`):
   - активные EventSource-запросы со статусом `200`;
   - нет ошибок `MIME type ("text/html")`;
   - нет повторяющихся reconnect-штормов.
4. В приложении выполнить действие, генерирующее live update (toast/source-status), и убедиться, что событие приходит в открытый SSE stream.
5. Наблюдать 15-30 минут:
   - в логах edge/nginx/mercure нет 4xx/5xx на SSE path;
   - нет массовых циклов `Subscriber disconnected -> New subscriber` без пользовательской активности.

#### 6.6.6 Анти-регрессия

- Не удалять и не ослаблять настройки из секции `6.5`.
- При изменениях proxy или TLS обязательно повторять smoke + stability проверку.
- Длинные e2e stability-тесты Mercure держать отдельным запуском (вне общего быстрого e2e-пайплайна).

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
- `NullNotificationBroadcaster`
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
