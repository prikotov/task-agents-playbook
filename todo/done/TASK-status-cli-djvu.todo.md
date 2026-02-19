# TASK-status-cli-djvu: DjVu health checker (Integration Layer)

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Ценность**: V3
- **Сложность**: C2
- **Приоритет**: P1
- **Зависит от**: TASK-status-health-application
- **Epic**: [EPIC-status-page](../EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: backend_developer
- **Ветка**: task/TASK-status-cli-djvu
- **PR**: #2112
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть работоспособность DjVu конвертера,
чтобы понимать доступность функции конвертации DjVu в PDF.

### Цель (SMART)
Реализовать health check для DjVu конвертера через Integration слой:
1) Query UseCase в Source Module для проверки здоровья;
2) Integration Service в Health Module (вызывает Query через QueryBus);
3) Переиспользование существующего DjVu компонента.

## 2. Контекст и Границы (Scope)
**Где делаем:**
- Source Module: `src/Module/Source/Application/UseCase/Query/CheckDjvuHealth/`
- Health Module: `src/Module/Health/Integration/Service/HealthChecker/`

**Архитектурный контекст (ADR-001):**
- Integration слой вызывает Source Module через QueryBus
- Переиспользуется существующий DjVu компонент
- Результат сохраняется в БД через cron на Worker Server

**Границы (Out of Scope):**
- Реальная конвертация DjVu файла
- Проверка качества конвертации
- Метрики скорости
- Console команда (отдельная задача TASK-status-cli-console)

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `DjvuHealthDto` с полями: isHealthy, version, errorMessage
- [x] Query `CheckDjvuHealthQuery` в Source Module
- [x] QueryHandler `CheckDjvuHealthQueryHandler` использующий DjVu компонент
- [x] Service `DjvuHealthCheckerService` в Health/Integration/Service через QueryBus
- [x] Проверка: ddjvu binary exists и executable
- [x] Unit тесты для QueryHandler

### 🟡 Should Have
- [x] Возврат версии DjVuLibre

### 🟢 Could Have
- [ ] Проверка поддерживаемых форматов

### ⚫ Won't Have
- [ ] Реальная конвертация
- [ ] Прямой вызов DjVu компонента из Health Module (нарушение конвенции)

## 4. План реализации (Tasks)

### Phase 1: Source Module Query UseCase
1. [x] Создать `DjvuHealthDto.php` рядом с QueryHandler (в CheckDjvuHealth/)
2. [x] Создать `CheckDjvuHealthQuery.php` в Source/Application/UseCase/Query/CheckDjvuHealth
3. [x] Создать `CheckDjvuHealthQueryHandler.php` использующий существующий DjvuComponent
4. [x] Написать Unit тест для QueryHandler

### Phase 2: Health Module Integration Service
5. [x] Создать `DjvuHealthCheckerService.php` в Health/Integration/Service/HealthChecker
6. [x] Обновить DI конфигурацию (Health module)

## 5. Структура файлов

```
src/Module/Source/
└── Application/
    └── UseCase/
        └── Query/
            └── CheckDjvuHealth/
                ├── CheckDjvuHealthQuery.php       # создан
                ├── CheckDjvuHealthQueryHandler.php # создан (использует DjvuComponent)
                └── DjvuHealthDto.php              # создан (рядом с use case)

src/Module/Health/
└── Integration/
    └── Service/
        └── HealthChecker/
            └── DjvuHealthCheckerService.php       # создан
```

## 6. Критерии приемки (Definition of Ready)
- [x] QueryHandler корректно использует DjVu компонент
- [x] Integration Service вызывает Query через QueryBus
- [x] Результат маппится в HealthCheckResultVo
- [x] Unit тесты проходят
- [x] DI конфигурация корректна

## 7. Самопроверка (Verification)
```bash
make tests-unit
make tests-integration
make check
```

## 8. Риски и Зависимости
- DjVuLibre должен быть установлен в системе
- QueryBus должен быть настроен для cross-module communication

## 9. Источники
- [ADR-001 в EPIC-status-page](../EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [DjvuConversionService](../src/Module/Source/Infrastructure/Service/Source/Djvu/)
- [DjVuLibre](https://djvu.sourceforge.net/)
- [Конвенция Application Layer](../docs/conventions/layers/application.md)

## 10. Комментарии
Путь к ddjvu должен быть конфигурируемым через параметры. Подход переиспользования DjVu компонента через QueryBus соответствует конвенции project layers.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-14 | backend_developer | Обновление под архитектуру ADR-001 (Integration слой через QueryBus) |
| 2026-02-15 | backend_developer | Выполнение задачи: DjvuHealthDto, QueryHandler (использует существующий DjvuComponent), DjvuHealthCheckerService, Unit тесты |
