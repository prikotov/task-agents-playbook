# TASK-status-ext-email: Email service health checker (Integration Layer)

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
- **Ветка**: task/TASK-status-ext-email
- **PR**: #2118
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть доступность email сервиса,
чтобы понимать работоспособность функции отправки уведомлений.

### Цель (SMART)
Реализовать health check для Email сервиса через Integration слой:
1) Query UseCase в Notification Module для проверки здоровья;
2) Integration Service в Health Module (вызывает Query через QueryBus);
3) Использование существующего Symfony Mailer компонента.

## 2. Контекст и Границы (Scope)
**Где делаем:**
- Notification Module: `src/Module/Notification/Application/UseCase/Query/CheckEmailHealth/`
- Health Module: `src/Module/Health/Integration/Service/HealthChecker/`

**Архитектурный контекст (ADR-002):**
- Integration слой вызывает Notification Module через QueryBus
- Переиспользуется существующий Symfony Mailer (transport)
- Не создаётся новый компонент - используется DI Symfony Mailer

**Границы (Out of Scope):**
- Реальная отправка писем
- Проверка deliverability
- Проверка SPF/DKIM/DMARC

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `EmailHealthDto` в `Notification/Application/Dto/` с полями: isHealthy, errorMessage
- [x] Query `CheckEmailHealthQuery` в Notification Module
- [x] QueryHandler `CheckEmailHealthQueryHandler` использующий Mailer transport
- [x] Interface `CheckEmailHealthServiceInterface` в Health/Domain/Service/HealthChecker — используется общий CheckHealthServiceInterface
- [x] Service `EmailHealthCheckerService` в Health/Integration/Service через QueryBus
- [x] Обработка: connection timeout, auth failure
- [x] Unit тесты для QueryHandler
- [x] Integration тест для Service

### 🟡 Should Have
- [x] Поддержка разных провайдеров (SMTP, SendGrid, Postmark) — базовая реализация
- [ ] Graceful degradation при отсутствии конфигурации — не реализовано (Should Have)

### 🟢 Could Have
- [ ] Проверка очереди писем

### ⚫ Won't Have
- [ ] Реальная отправка писем

## 4. План реализации (Tasks)

### Phase 1: Notification Module Query UseCase
1. [x] Создать `EmailHealthDto.php` в `Notification/Application/Dto/`
2. [x] Создать `CheckEmailHealthQuery.php` в `Notification/Application/UseCase/Query/CheckEmailHealth/`
3. [x] Создать `CheckEmailHealthQueryHandler.php` использующий Mailer transport
4. [x] Написать Unit тест для QueryHandler

### Phase 2: Health Module Integration Service
5. [x] Создать `CheckEmailHealthServiceInterface.php` в `Health/Domain/Service/HealthChecker/` — используется общий CheckHealthServiceInterface
6. [x] Создать `EmailHealthCheckerService.php` в `Health/Integration/Service/HealthChecker/`
7. [x] Обновить DI конфигурацию (tag health.checker)
8. [x] Написать Integration тест

## 5. Структура файлов

```
src/Module/Notification/
├── Application/
│   ├── Dto/
│   │   └── EmailHealthDto.php                      # создан
│   └── UseCase/
│       └── Query/
│           └── CheckEmailHealth/
│               ├── CheckEmailHealthQuery.php        # создан
│               └── CheckEmailHealthQueryHandler.php # создан

src/Module/Health/
└── Integration/
    └── Service/
        └── HealthChecker/
            └── EmailHealthCheckerService.php        # создан
```

## 6. Критерии приемки (Definition of Ready)
- [x] QueryHandler корректно использует Mailer transport
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
- SMTP сервер должен быть доступен
- Credentials должны быть настроены
- QueryBus должен быть настроен для cross-module communication

## 9. Источники
- [x] [Notification Module](../../src/Module/Notification/)
- [x] [Symfony Mailer](https://symfony.com/doc/current/mailer.html)
- [x] [ADR-001 в EPIC-status-page](../EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [x] [Конвенция Application Layer](../../docs/conventions/layers/application.md)

## 10. Комментарии
- При отсутствии конфигурации возвращать статус 'skipped'.
- Подход переиспользования существующего Mailer через QueryBus соответствует ADR-001.
- **ВАЖНО:** DTO для Use Case размещаются в `Application/Dto/`, а не рядом с UseCase.
- Используется общий интерфейс `CheckHealthServiceInterface` вместо отдельного `CheckEmailHealthServiceInterface` — это правильное архитектурное решение (avoid overengineering).

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-16 | system_analyst | **Редизайн (ADR-002):** переход на Integration слой через QueryBus вместо создания нового HealthCheck компонента; использование существующего Symfony Mailer; добавлено требование о размещении DTO в Application/Dto/ |
| 2026-02-16 | backend_developer | Реализация: EmailHealthDto, CheckEmailHealthQuery/Handler, EmailHealthCheckerService, Unit и Integration тесты |
