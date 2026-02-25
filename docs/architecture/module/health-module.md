# Health Module

Модуль мониторинга состояния системы для платформы TasK.

## Обзор

Health Module предоставляет единую точку для проверки состояния всех компонентов системы:
- Инфраструктура: PostgreSQL, RabbitMQ, MinIO
- CLI инструменты: yt-dlp, whisper.cpp, djvu, pdf
- Внешние API: T-Bank, Email/SMTP
- LLM провайдеры: Ollama, OpenAI, GoogleAI, и др.

## Архитектура

Модуль следует DDD-архитектуре с разделением на слои:

```
src/Module/Health/
├── Domain/                    # Бизнес-логика
│   ├── Entity/
│   ├── ValueObject/
│   ├── Repository/
│   └── Service/
├── Application/               # Use Cases
│   ├── Dto/
│   ├── UseCase/
│   └── Service/
├── Infrastructure/            # Техническая реализация
│   ├── Component/
│   └── Repository/
└── Integration/               # Внешние модули
    └── Service/
```

### Ключевые принципы

1. **Infrastructure Health Checks** — прямые проверки в Infrastructure слое
2. **CLI Tools Health Checks** — Integration слой через QueryBus + Cron/DB (ADR-0001)
3. **External API/LLM Health Checks** — Integration слой через QueryBus (ADR-0002)

## Health API Endpoints

| Endpoint | Purpose | Response |
|----------|---------|----------|
| `GET /health` | Liveness probe | 200 OK или 503 |
| `GET /health/ready` | Readiness probe | Детальный статус зависимостей |

### Пример ответа `/health/ready`

```json
{
  "status": "operational",
  "checked_at": "2026-02-16T05:00:00+00:00",
  "services": {
    "database": {
      "status": "operational",
      "message": "Database connection is active",
      "response_time_ms": 1.23
    },
    "rabbitmq": {
      "status": "operational",
      "message": "RabbitMQ connection is active",
      "response_time_ms": 5.67
    },
    "minio": {
      "status": "operational",
      "message": "MinIO bucket is accessible",
      "response_time_ms": 12.34
    },
    "tbank": {
      "status": "operational",
      "message": "T-Bank API is operational",
      "response_time_ms": 156.78
    },
    "email": {
      "status": "operational",
      "message": "Email service is operational",
      "response_time_ms": 45.67
    }
  }
}
```

## Реализованные Health Checks

### Infrastructure (Phase1)

| Сервис | Component | Service | Статус |
|--------|-----------|---------|--------|
| PostgreSQL | DatabaseHealthCheckComponent | DatabaseHealthCheckerService | ✅ |
| RabbitMQ | RabbitMqHealthCheckComponent | RabbitMqHealthCheckerService | ✅ |
| MinIO | MinioHealthCheckComponent | MinioHealthCheckerService | ✅ |

### CLI Tools (Phase2)

| Сервис | Query UseCase | Integration Service | Статус |
|--------|---------------|---------------------|--------|
| yt-dlp | CheckYtDlpHealthQuery | YtDlpHealthCheckerService | ✅ |
| whisper.cpp | CheckWhisperHealthQuery | WhisperHealthCheckerService | ✅ |
| DjVu | CheckDjvuHealthQuery | DjvuHealthCheckerService | ✅ |
| PDF | CheckPdfHealthQuery | PdfHealthCheckerService | ✅ |

### External API (Phase3)

| Сервис | Module | Query UseCase | Integration Service | Статус |
|--------|--------|---------------|---------------------|--------|
| T-Bank | Billing | CheckTBankHealthQuery | TBankHealthCheckerService | ✅ |
| Email/SMTP | Notification | CheckEmailHealthQuery | EmailHealthCheckerService | ✅ |

### LLM Providers (Phase 4) ✅

| Сервис | Query UseCase | Integration Service | Метод проверки | Статус |
|--------|---------------|---------------------|----------------|--------|
| Ollama | CheckOllamaHealthQuery | OllamaHealthCheckerService | `tags()` - modelCount | ✅ |
| OpenAI | CheckOpenAiHealthQuery | OpenAiHealthCheckerService | `listModels()` - modelCount | ✅ |
| GoogleAI | CheckGoogleAiHealthQuery | GoogleAiHealthCheckerService | `listModels()` - modelCount | ✅ |
| GigaChat | CheckGigaChatHealthQuery | GigaChatHealthCheckerService | `listModels()` - modelCount | ✅ |
| Fireworks | CheckFireworksHealthQuery | FireworksHealthCheckerService | `listModels()` - modelCount | ✅ |
| YandexFm | CheckYandexFmHealthQuery | YandexFmHealthCheckerService | `textEmbedding()` | ✅ |
| OpenRouter | CheckOpenRouterHealthQuery | OpenRouterHealthCheckerService | `getCredits()` - credits | ✅ |
| Cohere | CheckCohereHealthQuery | CohereHealthCheckerService | `embed()` | ✅ |
| DeepSeek | CheckDeepSeekHealthQuery | DeepSeekHealthCheckerService | `getBalance()` - balance | ✅ |

## Console Commands

### health:check:cli-tool

Проверка состояния CLI инструментов на Worker сервере.

```bash
# Проверить конкретный инструмент
bin/console health:check:cli-tool yt-dlp

# Проверить все инструменты
bin/console health:check:cli-tool --all

# Dry-run режим (без записи в DB)
bin/console health:check:cli-tool yt-dlp --dry-run

# JSON вывод
bin/console health:check:cli-tool yt-dlp --json
```

### Cron настройка

```bash
# crontab на Worker Server
* * * * * bin/console health:check:cli-tool yt-dlp
* * * * * bin/console health:check:cli-tool whisper
* * * * * bin/console health:check:cli-tool djvu
* * * * * bin/console health:check:cli-tool pdf
```

## Архитектурные решения

- [ADR-0001: CLI Tools Health Check Architecture](../adr/ADR-0001-cli-tools-health-check-architecture.md) — Integration слой через QueryBus + Cron/DB
- [ADR-0002: External API & LLM Health Check Architecture](../adr/ADR-0002-external-api-llm-health-check-architecture.md) — Integration слой через QueryBus

## Тестирование

### Unit тесты

```bash
make tests-unit
```

Покрывают:
- QueryHandler для всех типов health checks
- Маппинг DTO в HealthCheckResultVo
- Логику обработки ошибок

### Integration тесты

```bash
make tests-integration
```

Проверяют:
- Реальное подключение к сервисам
- QueryBus интеграцию между модулями
- DI конфигурацию

## Status Page (Phase 5) ✅

Публичная статус-страница доступна по адресу `/status`.

### Функции

- Отображение текущего статуса всех сервисов
- Группировка по категориям: Infrastructure, LLM, CLI Tools, External API
- Цветовая индикация статусов (operational/degraded/outage)
- Кэширование на 30 секунд
- Автообновление каждые 60 секунд (meta refresh)
- Мультиязычность (EN, RU, ZH)

### Структура в apps/web

```
apps/web/src/Module/Health/
├── Controller/
│   └── StatusController.php       # /status endpoint
├── Route/
│   └── StatusRoute.php
└── Resource/
    ├── config/
    │   └── services.yaml
    ├── templates/
    │   └── status/
    │       └── index.html.twig    # Bootstrap 5 Phoenix
    └── translations/
        ├── messages.en.yaml
        ├── messages.ru.yaml
        └── messages.zh.yaml
```

## Incident Management (Phase 6) ✅

Система управления инцидентами для информирования пользователей о проблемах.

### Domain слой

| Компонент | Описание |
|-----------|----------|
| `IncidentModel` | Entity с полями: uuid, title, description, status, severity, affectedServiceNames |
| `IncidentTitleVo` | Value Object для заголовка (3-255 chars) |
| `IncidentDescriptionVo` | Value Object для описания (max 10000 chars) |
| `IncidentStatusEnum` | investigating, identified, monitoring, resolved |
| `IncidentSeverityEnum` | minor, major, critical |

### Application слой

| Command/Query | Описание |
|---------------|----------|
| `CreateIncidentCommand` | Создание инцидента |
| `UpdateIncidentCommand` | Обновление инцидента |
| `ResolveIncidentCommand` | Решение инцидента |
| `DeleteIncidentCommand` | Удаление инцидента |
| `GetIncidentListQuery` | Получение списка инцидентов с фильтрацией |
| `GetIncidentQuery` | Получение конкретного инцидента |

### Admin UI

Управление инцидентами доступно авторизованным пользователям по адресу `/admin/incidents/`.

| Endpoint | Функция |
|----------|---------|
| `GET /admin/incidents` | Список инцидентов с пагинацией |
| `GET /admin/incidents/new` | Форма создания инцидента |
| `POST /admin/incidents/new` | Создание инцидента |
| `GET /admin/incidents/{uuid}/edit` | Форма редактирования |
| `POST /admin/incidents/{uuid}/edit` | Обновление инцидента |
| `POST /admin/incidents/{uuid}/resolve` | Решение инцидента |
| `POST /admin/incidents/{uuid}/delete` | Удаление инцидента |

### Status Page Integration

Активные инциденты отображаются на публичной статус-странице в блоке "Active Incidents":
- Цветовая индикация severity: critical (red), major (orange), minor (yellow)
- Фильтрация только активных инцидентов (не resolved)

### Структура БД

Таблица `health_incident`:
- id (bigint, PK)
- uuid (uuid, unique)
- title (varchar(255))
- description (text, nullable)
- status (varchar(20))
- severity (varchar(20))
- affected_service_names (json, nullable)
- ins_ts (timestamp)
- upd_ts (timestamp, nullable)
- resolved_at (timestamptz, nullable)

Индексы: status, ins_ts, severity

## См. также

- [EPIC-status-page](../../todo/EPIC-status-page.todo.md) — Epic задачи
- [Infrastructure Containers](../infrastructure-containers.md) — Инфраструктура
- [ADR-0001: CLI Tools Health Check Architecture](../adr/ADR-0001-cli-tools-health-check-architecture.md)
- [ADR-0002: External API & LLM Health Check Architecture](../adr/ADR-0002-external-api-llm-health-check-architecture.md)
