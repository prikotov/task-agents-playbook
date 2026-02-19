# TASK-status-ext-tbank: T-Bank health checker (Integration Layer)

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Ценность**: V3
- **Сложность**: C2
- **Приоритет**: P2
- **Зависит от**: TASK-status-health-application
- **Epic**: [EPIC-status-page](done/EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: backend_developer
- **Ветка**: task/TASK-status-ext-tbank
- **PR**: #2117
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть доступность T-Bank API,
чтобы понимать работоспособность платёжных функций.

### Цель (SMART)
Реализовать health check для T-Bank API через Integration слой:
1) Query UseCase в Billing Module для проверки здоровья;
2) Integration Service в Health Module (вызывает Query через QueryBus);
3) Расширение существующего TBusinessPaymentsComponent методом healthCheck().

## 2. Контекст и Границы (Scope)
**Где делаем:**
- Billing Module: `src/Module/Billing/Application/UseCase/Query/CheckTBankHealth/`
- Health Module: `src/Module/Health/Integration/Service/HealthChecker/`

**Архитектурный контекст (ADR-002):**
- Integration слой вызывает Billing Module через QueryBus
- Расширяется существующий TBusinessPaymentsComponent (не создаётся новый)
- Переиспользуется существующая конфигурация (HttpClient, credentials)

**Границы (Out of Scope):**
- Реальные транзакции
- Проверка баланса
- Детальная диагностика API

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `TBankHealthDto` в `Billing/Application/Dto/` с полями: isHealthy, errorMessage
- [x] Query `CheckTBankHealthQuery` в Billing Module
- [x] QueryHandler `CheckTBankHealthQueryHandler` использующий TBusinessPaymentsComponent
- [x] Interface `TBankHealthCheckServiceInterface` в Billing/Domain/Service/Payment
- [x] Service `TBankHealthCheckerService` в Health/Integration/Service через QueryBus
- [x] Обработка: connection timeout, auth failure
- [x] Unit тесты для QueryHandler
- [x] Integration тест для Service

### 🟡 Should Have
- [x] Проверка валидности credentials (лёгкий API call)
- [ ] Graceful degradation при отсутствии конфигурации

### 🟢 Could Have
- [ ] Проверка sandbox vs production endpoint

### ⚫ Won't Have
- [ ] Реальные транзакции

## 4. План реализации (Tasks)

### Phase 1: Billing Module Query UseCase
1. [x] Создать `TBankHealthDto.php` в `Billing/Application/Dto/`
2. [x] Создать `CheckTBankHealthQuery.php` в `Billing/Application/UseCase/Query/CheckTBankHealth/`
3. [x] Создать `CheckTBankHealthQueryHandler.php` использующий TBusinessPaymentsComponent
4. [x] Написать Unit тест для QueryHandler

### Phase 2: Health Module Integration Service
5. [x] Создать `TBankHealthCheckServiceInterface.php` в `Billing/Domain/Service/Payment/`
6. [x] Создать `TBankHealthCheckerService.php` в `Health/Integration/Service/HealthChecker/`
7. [x] Обновить DI конфигурацию (tag health.checker)
8. [x] Написать Integration тест

## 5. Структура файлов

```
src/Module/Billing/
├── Application/
│   ├── Dto/
│   │   └── TBankHealthDto.php                      # новый
│   └── UseCase/
│       └── Query/
│           └── CheckTBankHealth/
│               ├── CheckTBankHealthQuery.php        # новый
│               └── CheckTBankHealthQueryHandler.php # новый

src/Module/Health/
├── Domain/
│   └── Service/
│       └── HealthChecker/
│           └── CheckTBankHealthServiceInterface.php # новый
└── Integration/
    └── Service/
        └── HealthChecker/
            └── TBankHealthCheckerService.php        # новый
```

## 6. Критерии приемки (Definition of Ready)
- [x] QueryHandler корректно использует TBusinessPaymentsComponent
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
- T-Bank API должен быть доступен
- Credentials должны быть настроены
- QueryBus должен быть настроен для cross-module communication

## 9. Источники
- [ ] [T-Bank API](https://www.tbank.ru/kassa/dev/)
- [x] [TBusinessPaymentsComponent](../src/Module/Billing/Integration/Component/TBusiness/)
- [x] [ADR-001 в EPIC-status-page](EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [x] [Конвенция Application Layer](../docs/conventions/layers/application.md)

## 10. Комментарии
- При отсутствии credentials возвращать статус 'skipped'.
- Подход расширения существующего компонента соответствует ADR-001 для CLI tools.
- **ВАЖНО:** DTO для Use Case размещаются в `Application/Dto/`, а не рядом с UseCase.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-16 | system_analyst | **Редизайн (ADR-002):** переход на Integration слой через QueryBus вместо создания нового HealthCheck компонента; расширение существующего TBusinessPaymentsComponent; добавлено требование о размещении DTO в Application/Dto/ |
| 2026-02-16 | backend_developer | **Реализация:** TBankHealthDto, CheckTBankHealthQuery/Handler, TBankHealthCheckServiceInterface, TBankHealthCheckService, TBankHealthCheckerService, Unit и Integration тесты; расширен TBusinessPaymentsComponent методом healthCheck() |
