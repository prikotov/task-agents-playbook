# TASK-status-llm-cohere: Cohere LLM health checker (Integration Layer)

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
я хочу видеть доступность Cohere API,
чтобы понимать работоспособность AI функций использующих Cohere модели.

### Цель (SMART)
Реализовать health check для Cohere через Integration слой:
1) Query UseCase в Llm Module для проверки здоровья;
2) Integration Service в Health Module (вызывает Query через QueryBus);
3) Расширение существующего CohereComponent или переиспользование embed().

## 2. Контекст и Границы (Scope)
**Где делаем:**
- Llm Module: `src/Module/Llm/Application/UseCase/Query/CheckCohereHealth/`
- Health Module: `src/Module/Health/Integration/Service/HealthChecker/`

**Архитектурный контекст (ADR-002):**
- Integration слой вызывает Llm Module через QueryBus
- Расширяется/переиспользуется существующий CohereComponent (не создаётся новый)
- Переиспользуется существующая конфигурация (HttpClient, apiKey)

**Границы (Out of Scope):**
- Реальная генерация текста
- Проверка качества моделей
- Детальные метрики usage

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `CohereHealthDto` в `Llm/Application/UseCase/Query/CheckCohereHealth/` с полями: isHealthy, errorMessage
- [x] Query `CheckCohereHealthQuery` в Llm Module
- [x] QueryHandler `CheckCohereHealthQueryHandler` использующий CohereComponent
- [x] Interface `CheckCohereHealthServiceInterface` в Health/Domain/Service/HealthChecker
- [x] Service `CohereHealthCheckerService` в Health/Integration/Service через QueryBus
- [x] Обработка: connection refused, timeout, auth error
- [x] Unit тесты для QueryHandler
- [x] Integration тест для Service

### 🟡 Should Have
- [x] Проверка доступности моделей (command, embed)
- [x] Graceful degradation при отсутствии конфигурации

### 🟢 Could Have
- [x] Информация о credit balance

### ⚫ Won't Have
- [x] Реальная генерация текста

## 4. План реализации (Tasks)

### Phase 1: Llm Module Query UseCase
1. [ ] Создать `CohereHealthDto.php` в `Llm/Application/UseCase/Query/CheckCohereHealth/`
2. [ ] Создать `CheckCohereHealthQuery.php` в `Llm/Application/UseCase/Query/CheckCohereHealth/`
3. [ ] Создать `CheckCohereHealthQueryHandler.php` использующий CohereComponent
4. [ ] Написать Unit тест для QueryHandler

### Phase 2: Health Module Integration Service
5. [ ] Создать `CheckCohereHealthServiceInterface.php` в `Health/Domain/Service/HealthChecker/`
6. [ ] Создать `CohereHealthCheckerService.php` в `Health/Integration/Service/HealthChecker/`
7. [ ] Обновить DI конфигурацию (tag health.checker)
8. [ ] Написать Integration тест

## 5. Структура файлов

```
src/Module/Llm/
├── Application/
│   └── UseCase/
│       └── Query/
│           └── CheckCohereHealth/
│               ├── CohereHealthDto.php              # новый
│               ├── CheckCohereHealthQuery.php       # новый
│               └── CheckCohereHealthQueryHandler.php # новый

src/Module/Health/
├── Domain/
│   └── Service/
│       └── HealthChecker/
│           └── CheckCohereHealthServiceInterface.php # новый
└── Integration/
    └── Service/
        └── HealthChecker/
            └── CohereHealthCheckerService.php       # новый
```

## 6. Критерии приемки (Definition of Ready)
- [x] QueryHandler корректно использует CohereComponent
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
- Cohere API должен быть доступен
- API key должен быть настроен
- QueryBus должен быть настроен для cross-module communication

## 9. Источники
- [x] [Cohere API](https://docs.cohere.com/)
- [x] [CohereComponent](../src/Module/Llm/Infrastructure/Component/Cohere/)
- [x] [ADR-001 в EPIC-status-page](EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [x] [Конвенция Application Layer](../docs/conventions/layers/application.md)

## 10. Комментарии
- Cohere предоставляет как generation, так и embedding модели.
- **ВАЖНО:** DTO размещается рядом с UseCase, который его возвращает.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-16 | system_analyst | **Редизайн (ADR-002):** переход на Integration слой через QueryBus вместо создания нового HealthCheck компонента; переиспользование существующего CohereComponent |
| 2026-02-16 | system_analyst | Корректировка размещения DTO: рядом с UseCase, а не в Application/Dto/ |
| 2026-02-16 | backend_developer | **Выполнено** (PR #2121): реализованы DTO, Query, QueryHandler, Interface, Integration Service, Unit и Integration тесты |
