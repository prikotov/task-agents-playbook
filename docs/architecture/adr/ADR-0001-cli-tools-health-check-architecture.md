# ADR-0001: CLI Tools Health Check Architecture

**Status:** Accepted
**Date:** 2026-02-14
**Deciders:** backend_developer

## Context

Health Module должен проверять состояние CLI инструментов (yt-dlp, whisper.cpp, djvu, pdf), которые установлены только на Worker сервере. Текущая реализация имела две критические проблемы:

### Проблема 1: Дублирование конфигурации

`YtDlpHealthCheckComponent` в Health Module независимо от `YtDlpComponent` в Source Module.

**Риск:** Конфигурации могут разойтись → health check проверяет не тот binary.

### Проблема 2: Распределённая архитектура

CLI tools установлены только на Worker сервере, а Health check выполняется на Web сервере.

**Текущее поведение:** Health check с Web сервера всегда вернёт `outage`.

## Decision

### 1. Integration слой через Query Bus

Согласно `docs/conventions/layers/application.md`, из внешних модулей можно вызывать только **Use Cases** (Query/Command handlers).

```
Health Module / Integration
    ↓ вызывает через QueryBus
Source Module / Application UseCase (Query)
    ↓ использует
Source Module / Infrastructure (существующий YtDlpComponent)
```

### 2. Cron + DB для Worker CLI tools

Каждый CLI tool проверяется отдельной cron записью для параллельного выполнения:

```bash
# crontab на Worker Server
* * * * * bin/console health:check:cli-tool yt-dlp
* * * * * bin/console health:check:cli-tool whisper
* * * * * bin/console health:check:cli-tool djvu
* * * * * bin/console health:check:cli-tool pdf
```

### Структура модулей

#### Source Module (Query UseCase)

```
src/Module/Source/
├── Application/
│   ├── Dto/
│   │   └── YtDlpHealthDto.php
│   └── UseCase/
│       └── Query/
│           └── CheckYtDlpHealth/
│               ├── CheckYtDlpHealthQuery.php
│               └── CheckYtDlpHealthQueryHandler.php
```

#### Health Module (Integration Service реализует CheckHealthServiceInterface)

```
src/Module/Health/
├── Domain/
│   └── Service/
│       └── HealthChecker/
│           └── CheckHealthServiceInterface.php     # общий интерфейс для Registry
├── Integration/
│   └── Service/
│       └── HealthChecker/
│           └── YtDlpHealthCheckerService.php       # реализует CheckHealthServiceInterface
```

## Alternatives

### Прямой вызов Service (отклонено)

Integration напрямую вызывает Source/Infrastructure Service.

**Причина отклонения:** Нарушает `docs/conventions/layers/application.md` — Integration может вызывать только UseCases.

### Worker Internal API (отклонено)

Worker предоставляет HTTP endpoint для health checks.

**Причина отклонения:** Сложнее в реализации, требует дополнительного HTTP endpoint на Worker.

### RabbitMQ (отклонено)

Health checks через message queue.

**Причина отклонения:** Overkill для простой проверки, добавляет latency.

## Consequences

### Positive

- Переиспользование существующего YtDlpComponent без дублирования
- Соответствие конвенции Integration → Application Query
- Параллельное выполнение проверок CLI tools
- Независимые таймауты для каждого инструмента
- Гибкая настройка частоты для каждого сервиса

### Negative

- Задержка обновления статуса (до 60 сек) — приемлемо для CLI tools
- Worker недоступен → статус устаревает

### Technical Debt

- Требуется реализовать Console команду `health:check:cli-tool` для cron
- Требуется добавить проверку `last_check_at` в API для обнаружения устаревших данных

## Revisit Criteria

Пересмотреть решение если:
- Требуется real-time статус CLI tools (уменьшить cron interval)
- Worker серверов станет много (потребуется агрегация статусов)
- Появится необходимость в health checks с Web сервера

## Links

- [EPIC-status-page](../../todo/EPIC-status-page.todo.md) — Epic задачи
- [layers/application.md](../conventions/layers/application.md) — Конвенция слоёв
- [core_patterns/service.md](../conventions/core_patterns/service.md) — Конвенция сервисов

## History

| Date | Author | Change |
| :--- | :--- | :--- |
| 2026-02-14 | backend_developer | Создание ADR |
