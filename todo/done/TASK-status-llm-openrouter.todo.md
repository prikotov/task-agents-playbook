# TASK-status-llm-openrouter: OpenRouter LLM health checker (Integration Layer)

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Ценность**: V3
- **Сложность**: C2
- **Приоритет**: P2
- **Зависит от**: TASK-status-health-application
- **Epic**: [EPIC-status-page](../EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: backend_developer
- **Ветка**: task/phase4-llm-health-checks
- **PR**: #2121
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть доступность OpenRouter API,
чтобы понимать работоспособность AI функций использующих модели через OpenRouter.

### Цель (SMART)
Реализовать health check для OpenRouter через Integration слой:
1) Query UseCase в Llm Module для проверки здоровья;
2) Integration Service в Health Module (вызывает Query через QueryBus);
3) Расширение существующего OpenRouterComponent или переиспользование getModels()/getCredits().

## 2. Контекст и Границы (Scope)
**Где делаем:**
- Llm Module: `src/Module/Llm/Application/UseCase/Query/CheckOpenRouterHealth/`
- Health Module: `src/Module/Health/Integration/Service/HealthChecker/`

**Архитектурный контекст (ADR-002):**
- Integration слой вызывает Llm Module через QueryBus
- Расширяется/переиспользуется существующий OpenRouterComponent (не создаётся новый)
- Переиспользуется существующая конфигурация (HttpClient, apiKey)

**Границы (Out of Scope):**
- Реальная генерация текста
- Проверка качества моделей
- Детальные метрики usage

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `OpenRouterHealthDto` в `Llm/Application/UseCase/Query/CheckOpenRouterHealth/` с полями: isHealthy, creditsRemaining, errorMessage
- [x] Query `CheckOpenRouterHealthQuery` в Llm Module
- [x] QueryHandler `CheckOpenRouterHealthQueryHandler` использующий OpenRouterComponent
- [x] Interface `CheckOpenRouterHealthServiceInterface` в Health/Domain/Service/HealthChecker
- [x] Service `OpenRouterHealthCheckerService` в Health/Integration/Service через QueryBus
- [x] Обработка: connection refused, timeout, auth error
- [x] Unit тесты для QueryHandler
- [x] Integration тест для Service

### 🟡 Should Have
- [x] Проверка credit balance
- [x] Graceful degradation при отсутствии конфигурации

### 🟢 Could Have
- [x] Информация о доступных провайдерах

### ⚫ Won't Have
- [x] Реальная генерация текста

## 4. План реализации (Tasks)

### Phase 1: Llm Module Query UseCase
1. [ ] Создать `OpenRouterHealthDto.php` в `Llm/Application/UseCase/Query/CheckOpenRouterHealth/`
2. [ ] Создать `CheckOpenRouterHealthQuery.php` в `Llm/Application/UseCase/Query/CheckOpenRouterHealth/`
3. [ ] Создать `CheckOpenRouterHealthQueryHandler.php` использующий OpenRouterComponent
4. [ ] Написать Unit тест для QueryHandler

### Phase 2: Health Module Integration Service
5. [ ] Создать `CheckOpenRouterHealthServiceInterface.php` в `Health/Domain/Service/HealthChecker/`
6. [ ] Создать `OpenRouterHealthCheckerService.php` в `Health/Integration/Service/HealthChecker/`
7. [ ] Обновить DI конфигурацию (tag health.checker)
8. [ ] Написать Integration тест

## 5. Структура файлов

```
src/Module/Llm/
├── Application/
│   └── UseCase/
│       └── Query/
│           └── CheckOpenRouterHealth/
│               ├── OpenRouterHealthDto.php          # новый
│               ├── CheckOpenRouterHealthQuery.php   # новый
│               └── CheckOpenRouterHealthQueryHandler.php # новый

src/Module/Health/
├── Domain/
│   └── Service/
│       └── HealthChecker/
│           └── CheckOpenRouterHealthServiceInterface.php # новый
└── Integration/
    └── Service/
        └── HealthChecker/
            └── OpenRouterHealthCheckerService.php   # новый
```

## 6. Критерии приемки (Definition of Ready)
- [x] QueryHandler корректно использует OpenRouterComponent
- [x] Integration Service вызывает Query через QueryBus
- [x] Результат маппится в HealthCheckResultVo
- [x] Unit и Integration тесты проходят
- [x] DI конфигурация корректна

## 7. Самопроверка (Verification)
```bash
make tests-unit
make tests-integration
make check
```

## 8. Риски и Зависимости
- OpenRouter API должен быть доступен
- API key должен быть настроен
- QueryBus должен быть настроен для cross-module communication

## 9. Источники
- [x] [OpenRouter API](https://openrouter.ai/docs)
- [x] [OpenRouterComponent](../src/Module/Llm/Infrastructure/Component/OpenRouter/)
- [x] [ADR-001 в EPIC-status-page](EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [x] [Конвенция Application Layer](../docs/conventions/layers/application.md)

## 10. Комментарии
- OpenRouter агрегирует множество LLM провайдеров.
- **ВАЖНО:** DTO размещается рядом с UseCase, который его возвращает.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-16 | system_analyst | **Редизайн (ADR-002):** переход на Integration слой через QueryBus вместо создания нового HealthCheck компонента; переиспользование существующего OpenRouterComponent |
| 2026-02-16 | system_analyst | Корректировка размещения DTO: рядом с UseCase, а не в Application/Dto/ |
| 2026-02-16 | backend_developer | **Выполнено** (PR #2121): реализованы DTO, Query, QueryHandler, Interface, Integration Service, Unit и Integration тесты |
