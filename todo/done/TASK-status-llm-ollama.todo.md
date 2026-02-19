# TASK-status-llm-ollama: Ollama LLM health checker (Integration Layer)

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
я хочу видеть доступность Ollama LLM провайдера,
чтобы понимать работоспособность AI функций использующих локальные модели.

### Цель (SMART)
Реализовать health check для Ollama через Integration слой:
1) Query UseCase в Llm Module для проверки здоровья;
2) Integration Service в Health Module (вызывает Query через QueryBus);
3) Расширение существующего OllamaComponent методом healthCheck() или переиспользование tags().

## 2. Контекст и Границы (Scope)
**Где делаем:**
- Llm Module: `src/Module/Llm/Application/UseCase/Query/CheckOllamaHealth/`
- Health Module: `src/Module/Health/Integration/Service/HealthChecker/`

**Архитектурный контекст (ADR-002):**
- Integration слой вызывает Llm Module через QueryBus
- Расширяется/переиспользуется существующий OllamaComponent (не создаётся новый)
- Переиспользуется существующая конфигурация (HttpClient, baseUrl)

**Границы (Out of Scope):**
- Реальная генерация текста
- Проверка качества моделей
- Метрики latency

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `OllamaHealthDto` в `Llm/Application/UseCase/Query/CheckOllamaHealth/` с полями: isHealthy, version, modelsCount, errorMessage
- [x] Query `CheckOllamaHealthQuery` в Llm Module
- [x] QueryHandler `CheckOllamaHealthQueryHandler` использующий OllamaComponent.tags()
- [x] Interface `CheckOllamaHealthServiceInterface` в Health/Domain/Service/HealthChecker
- [x] Service `OllamaHealthCheckerService` в Health/Integration/Service через QueryBus
- [x] Обработка: connection refused, timeout
- [x] Unit тесты для QueryHandler
- [x] Integration тест для Service

### 🟡 Should Have
- [x] Список доступных моделей в результате
- [x] Graceful degradation при отсутствии конфигурации

### 🟢 Could Have
- [x] Информация о версии Ollama

### ⚫ Won't Have
- [ ] Реальная генерация текста

## 4. План реализации (Tasks)

### Phase 1: Llm Module Query UseCase
1. [x] Создать `OllamaHealthDto.php` в `Llm/Application/UseCase/Query/CheckOllamaHealth/`
2. [x] Создать `CheckOllamaHealthQuery.php` в `Llm/Application/UseCase/Query/CheckOllamaHealth/`
3. [x] Создать `CheckOllamaHealthQueryHandler.php` использующий OllamaComponent.tags()
4. [x] Написать Unit тест для QueryHandler

### Phase 2: Health Module Integration Service
5. [x] Создать `CheckOllamaHealthServiceInterface.php` в `Health/Domain/Service/HealthChecker/`
6. [x] Создать `OllamaHealthCheckerService.php` в `Health/Integration/Service/HealthChecker/`
7. [x] Обновить DI конфигурацию (tag health.checker)
8. [x] Написать Integration тест

## 5. Структура файлов

```
src/Module/Llm/
├── Application/
│   └── UseCase/
│       └── Query/
│           └── CheckOllamaHealth/
│               ├── OllamaHealthDto.php              # новый
│               ├── CheckOllamaHealthQuery.php       # новый
│               └── CheckOllamaHealthQueryHandler.php # новый

src/Module/Health/
├── Domain/
│   └── Service/
│       └── HealthChecker/
│           └── CheckOllamaHealthServiceInterface.php # новый
└── Integration/
    └── Service/
        └── HealthChecker/
            └── OllamaHealthCheckerService.php       # новый
```

## 6. Критерии приемки (Definition of Ready)
- [x] QueryHandler корректно использует OllamaComponent.tags()
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
- Ollama сервер должен быть запущен
- QueryBus должен быть настроен для cross-module communication

## 9. Источники
- [ ] [Ollama API](https://github.com/ollama/ollama/blob/main/docs/api.md)
- [x] [OllamaComponent](../src/Module/Llm/Infrastructure/Component/Ollama/)
- [x] [ADR-001 в EPIC-status-page](EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [x] [Конвенция Application Layer](../docs/conventions/layers/application.md)

## 10. Комментарии
- При отсутствии Ollama возвращать статус 'degraded' если есть другие провайдеры.
- Метод tags() уже существует в OllamaComponent - идеально подходит для health check.
- **ВАЖНО:** DTO размещается рядом с UseCase, который его возвращает.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-16 | system_analyst | **Редизайн (ADR-002):** переход на Integration слой через QueryBus вместо создания нового HealthCheck компонента; переиспользование существующего OllamaComponent.tags() |
| 2026-02-16 | system_analyst | Корректировка размещения DTO: рядом с UseCase, а не в Application/Dto/ |
| 2026-02-16 | backend_developer | **Выполнено** (PR #2121): реализованы DTO, Query, QueryHandler, Interface, Integration Service, Unit и Integration тесты |
