# ADR-0002: External API & LLM Health Check Architecture

**Status:** Accepted
**Date:** 2026-02-16
**Deciders:** system_analyst, backend_developer

## Context

Health Module должен проверять состояние внешних API (T-Bank, Email/SMTP) и LLM провайдеров (Ollama, OpenAI, GoogleAI, GigaChat, Fireworks, YandexFm, OpenRouter, Cohere, DeepSeek). Текущая реализация имеет те же проблемы, что и CLI tools:

### Проблема 1: Дублирование конфигурации

Компоненты для работы с внешними API уже существуют:
- `OllamaComponent`, `OpenAiComponent`, и др. в Llm Module
- `TBusinessPaymentsComponent` в Billing Module
- Symfony Mailer в Notification Module

Создание отдельных HealthCheck компонентов в Health Module приведёт к дублированию конфигурации (HttpClient, credentials, timeouts).

### Проблема 2: Сложность поддержки

При изменении конфигурации LLM/API провайдеров пришлось бы обновлять конфигурацию в двух местах.

## Decision

### Integration слой через Query Bus (расширение ADR-0001)

```
Health Module / Integration Service
    ↓ вызывает через QueryBus
Llm Module / Application UseCase (Query)
    ↓ использует
Llm Module / Infrastructure (существующий Component)
```

То же самое для External API:

```
Health Module / Integration Service
    ↓ вызывает через QueryBus
Billing Module / Application UseCase (Query)
    ↓ использует
Billing Module / Integration (существующий TBusinessPaymentsComponent)
```

### Общий pattern для LLM/External API health checks

1. **DTO** в `{Module}/Application/Dto/` с полями: `isHealthy`, `errorMessage`, дополнительные метрики
2. **Query UseCase** в соответствующем модуле (Llm, Billing, Notification)
3. **Integration Service** в Health Module (вызывает Query через QueryBus)

### Структура модулей

#### Llm Module (пример для Ollama)

```
src/Module/Llm/
├── Application/
│   ├── Dto/
│   │   └── OllamaHealthDto.php
│   └── UseCase/
│       └── Query/
│           └── CheckOllamaHealth/
│               ├── CheckOllamaHealthQuery.php
│               └── CheckOllamaHealthQueryHandler.php  # использует OllamaComponent
```

#### Billing Module (T-Bank)

```
src/Module/Billing/
├── Application/
│   ├── Dto/
│   │   └── TBankHealthDto.php
│   └── UseCase/
│       └── Query/
│           └── CheckTBankHealth/
│               ├── CheckTBankHealthQuery.php
│               └── CheckTBankHealthQueryHandler.php  # использует TBankHealthCheckServiceInterface
```

#### Health Module (Integration Service)

```
src/Module/Health/
└── Integration/
    └── Service/
        └── HealthChecker/
            ├── OllamaHealthCheckerService.php    # реализует CheckHealthServiceInterface
            ├── TBankHealthCheckerService.php     # реализует CheckHealthServiceInterface
            └── EmailHealthCheckerService.php     # реализует CheckHealthServiceInterface
```

## Alternatives

### Прямой вызов Component (отклонено)

Integration напрямую вызывает Infrastructure Component другого модуля.

**Причина отклонения:** Нарушает `docs/conventions/layers/application.md` — Integration может вызывать только UseCases.

### Создание HealthCheck компонентов в Health Module (отклонено)

Дублирование конфигурации HttpClient и credentials.

**Причина отклонения:** 
- Дублирование конфигурации
- Сложность поддержки при изменении credentials
- Несоответствие DRY принципу

## Consequences

### Positive

- Переиспользование существующей конфигурации без дублирования
- Нет дублирования кода
- Единая точка настройки credentials
- Соответствие конвенции Integration → Application Query
- Расширение существующих компонентов (healthCheck метод) вместо создания новых

### Negative

- Зависимость от QueryBus для cross-module communication
- Требуется расширение существующих компонентов методом healthCheck()

### Реализованные компоненты

#### External API (Phase 3)

| Сервис | Module | DTO | Query | Integration Service | PR |
|--------|--------|-----|-------|---------------------|-----|
| T-Bank | Billing | TBankHealthDto | CheckTBankHealthQuery | TBankHealthCheckerService | #2117 |
| Email/SMTP | Notification | EmailHealthDto | CheckEmailHealthQuery | EmailHealthCheckerService | #2118 |

#### LLM Providers (Phase 4) ✅

Все 9 LLM провайдеров реализованы (PR #2121):

| Сервис | DTO | Query | Integration Service | Метод проверки |
|--------|-----|-------|---------------------|----------------|
| Ollama | OllamaHealthDto | CheckOllamaHealthQuery | OllamaHealthCheckerService | `tags()` - modelCount |
| OpenAI | OpenAiHealthDto | CheckOpenAiHealthQuery | OpenAiHealthCheckerService | `listModels()` - modelCount |
| GoogleAI | GoogleAiHealthDto | CheckGoogleAiHealthQuery | GoogleAiHealthCheckerService | `listModels()` - modelCount |
| GigaChat | GigaChatHealthDto | CheckGigaChatHealthQuery | GigaChatHealthCheckerService | `listModels()` - modelCount |
| Fireworks | FireworksHealthDto | CheckFireworksHealthQuery | FireworksHealthCheckerService | `listModels()` - modelCount |
| YandexFm | YandexFmHealthDto | CheckYandexFmHealthQuery | YandexFmHealthCheckerService | `textEmbedding()` |
| OpenRouter | OpenRouterHealthDto | CheckOpenRouterHealthQuery | OpenRouterHealthCheckerService | `getCredits()` - creditsRemaining, creditsUsed |
| Cohere | CohereHealthDto | CheckCohereHealthQuery | CohereHealthCheckerService | `embed()` |
| DeepSeek | DeepSeekHealthDto | CheckDeepSeekHealthQuery | DeepSeekHealthCheckerService | `getBalance()` - balance |

## Revisit Criteria

Пересмотреть решение если:
- Требуется кэширование результатов health checks
- Появится необходимость в детальных метриках (latency, rate limits)
- LLM провайдеров станет много и потребуется группировка

## Links

- [EPIC-status-page](../../todo/EPIC-status-page.todo.md) — Epic задачи
- [ADR-0001: CLI Tools Health Check Architecture](ADR-0001-cli-tools-health-check-architecture.md) — Базовый ADR для Integration слоя
- [layers/application.md](../conventions/layers/application.md) — Конвенция слоёв

## History

| Date | Author | Change |
| :--- | :--- | :--- |
| 2026-02-16 | system_analyst | Создание ADR на основе ADR-002 из EPIC-status-page |
| 2026-02-16 | backend_developer | Реализация Phase 3: T-Bank и Email health checks |
| 2026-02-16 | backend_developer | Реализация Phase 4: все 9 LLM провайдеров (PR #2121) |
