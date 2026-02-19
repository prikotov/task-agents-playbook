# TASK-status-llm-yandexfm: Yandex Foundation Models health checker (Integration Layer)

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
я хочу видеть доступность Yandex Foundation Models API,
чтобы понимать работоспособность AI функций использующих YandexGPT.

### Цель (SMART)
Реализовать health check для Yandex FM через Integration слой:
1) Query UseCase в Llm Module для проверки здоровья;
2) Integration Service в Health Module (вызывает Query через QueryBus);
3) Расширение существующего YandexFmComponent или переиспользование существующих методов.

## 2. Контекст и Границы (Scope)
**Где делаем:**
- Llm Module: `src/Module/Llm/Application/UseCase/Query/CheckYandexFmHealth/`
- Health Module: `src/Module/Health/Integration/Service/HealthChecker/`

**Архитектурный контекст (ADR-002):**
- Integration слой вызывает Llm Module через QueryBus
- Расширяется/переиспользуется существующий YandexFmComponent (не создаётся новый)
- Переиспользуется существующая конфигурация (HttpClient, IAM token)

**Границы (Out of Scope):**
- Реальная генерация текста
- Проверка качества моделей
- Детальные метрики usage

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `YandexFmHealthDto` в `Llm/Application/UseCase/Query/CheckYandexFmHealth/` с полями: isHealthy, errorMessage
- [x] Query `CheckYandexFmHealthQuery` в Llm Module
- [x] QueryHandler `CheckYandexFmHealthQueryHandler` использующий YandexFmComponent
- [x] Interface `CheckYandexFmHealthServiceInterface` в Health/Domain/Service/HealthChecker
- [x] Service `YandexFmHealthCheckerService` в Health/Integration/Service через QueryBus
- [x] Обработка: connection refused, timeout, auth error
- [x] Unit тесты для QueryHandler
- [x] Integration тест для Service

### 🟡 Should Have
- [x] Проверка валидности IAM токена
- [x] Graceful degradation при отсутствии конфигурации

### 🟢 Could Have
- [x] Информация о доступных моделях

### ⚫ Won't Have
- [x] Реальная генерация текста

## 4. План реализации (Tasks)

### Phase 1: Llm Module Query UseCase
1. [ ] Создать `YandexFmHealthDto.php` в `Llm/Application/UseCase/Query/CheckYandexFmHealth/`
2. [ ] Создать `CheckYandexFmHealthQuery.php` в `Llm/Application/UseCase/Query/CheckYandexFmHealth/`
3. [ ] Создать `CheckYandexFmHealthQueryHandler.php` использующий YandexFmComponent
4. [ ] Написать Unit тест для QueryHandler

### Phase 2: Health Module Integration Service
5. [ ] Создать `CheckYandexFmHealthServiceInterface.php` в `Health/Domain/Service/HealthChecker/`
6. [ ] Создать `YandexFmHealthCheckerService.php` в `Health/Integration/Service/HealthChecker/`
7. [ ] Обновить DI конфигурацию (tag health.checker)
8. [ ] Написать Integration тест

## 5. Структура файлов

```
src/Module/Llm/
├── Application/
│   └── UseCase/
│       └── Query/
│           └── CheckYandexFmHealth/
│               ├── YandexFmHealthDto.php            # новый
│               ├── CheckYandexFmHealthQuery.php     # новый
│               └── CheckYandexFmHealthQueryHandler.php # новый

src/Module/Health/
├── Domain/
│   └── Service/
│       └── HealthChecker/
│           └── CheckYandexFmHealthServiceInterface.php # новый
└── Integration/
    └── Service/
        └── HealthChecker/
            └── YandexFmHealthCheckerService.php     # новый
```

## 6. Критерии приемки (Definition of Ready)
- [x] QueryHandler корректно использует YandexFmComponent
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
- Yandex Cloud API должен быть доступен
- IAM токен должен быть настроен
- QueryBus должен быть настроен для cross-module communication

## 9. Источники
- [x] [Yandex Foundation Models](https://cloud.yandex.ru/docs/yandexgpt/)
- [x] [YandexFmComponent](../src/Module/Llm/Infrastructure/Component/YandexFm/)
- [x] [ADR-001 в EPIC-status-page](EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [x] [Конвенция Application Layer](../docs/conventions/layers/application.md)

## 10. Комментарии
- Yandex использует IAM токены с ограниченным сроком жизни.
- **ВАЖНО:** DTO размещается рядом с UseCase, который его возвращает.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-16 | system_analyst | **Редизайн (ADR-002):** переход на Integration слой через QueryBus вместо создания нового HealthCheck компонента; переиспользование существующего YandexFmComponent |
| 2026-02-16 | system_analyst | Корректировка размещения DTO: рядом с UseCase, а не в Application/Dto/ |
| 2026-02-16 | backend_developer | **Выполнено** (PR #2121): реализованы DTO, Query, QueryHandler, Interface, Integration Service, Unit и Integration тесты |
