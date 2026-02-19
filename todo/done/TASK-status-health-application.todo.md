# TASK-status-health-application: Создать Application слой HealthModule

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Ценность**: V3
- **Сложность**: C2
- **Приоритет**: P1
- **Зависит от**: TASK-status-health-domain
- **Epic**: [EPIC-status-page](EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: backend_developer
- **Ветка**: task/TASK-status-health-application
- **PR**: #2099
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как разработчик,
я хочу иметь Application слой для health checks,
чтобы координировать проверки сервисов и возвращать результаты через DTO.

### Цель (SMART)
Создать Application слой модуля Health с:
1) Query handlers для получения статуса системы;
2) Command handler для обновления статуса;
3) DTO для передачи данных между слоями;
4) Registry service для управления health checkers.

## 2. Контекст и Границы (Scope)
**Где делаем:** `src/Module/Health/Application/`

**Границы (Out of Scope):**
- Domain слой (создаётся в TASK-status-health-domain)
- Infrastructure реализации (Component, Repository)
- Integration слой

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `SystemHealthDto` с общим статусом и списком сервисов
- [x] DTO `ServiceHealthDto` для отдельного сервиса
- [x] Query `GetSystemHealthQuery` + Handler
- [x] Query `GetServiceStatusQuery` + Handler (по имени сервиса)
- [x] Command `UpdateServiceStatusCommand` + Handler
- [x] Command `RunHealthChecksCommand` + Handler (для запуска проверок)
- [x] Service `HealthCheckerRegistryService` для регистрации чекеров
- [x] Application-layer enums: `ServiceStatusEnum`, `ServiceCategoryEnum`
- [x] Unit тесты для handlers и services

### 🟡 Should Have
- [ ] DTO `HealthCheckRequestDto` для параметров проверки
- [ ] Mapper для конвертации Entity → DTO

### 🟢 Could Have
- [ ] Application events: HealthCheckCompletedEvent

### ⚫ Won't Have
- [ ] Конкретные реализации health checkers

## 4. План реализации (Tasks)
1. [x] Создать директорию `src/Module/Health/Application/Dto/`
2. [x] Создать `SystemHealthDto.php`
3. [x] Создать `ServiceHealthDto.php`
4. [x] Создать `GetSystemHealthQuery.php` и `GetSystemHealthQueryHandler.php`
5. [x] Создать `GetServiceStatusQuery.php` и `GetServiceStatusQueryHandler.php`
6. [x] Создать `UpdateServiceStatusCommand.php` и `UpdateServiceStatusCommandHandler.php`
7. [x] Создать `RunHealthChecksCommand.php` и `RunHealthChecksCommandHandler.php`
8. [x] Создать `HealthCheckerRegistryService.php`
9. [x] Создать Application-layer enums
10. [x] Написать Unit тесты

## 5. Критерии приемки (Definition of Ready)
- [x] Query/Command handlers используют Domain interfaces
- [x] DTO `final readonly`
- [x] Unit тесты покрывают >= 80% кода
- [x] PHPStan level проходит без ошибок

## 6. Самопроверка (Verification)
```bash
make tests-unit
make check
```

## 7. Риски и Зависимости
- Зависит от TASK-status-health-domain

## 8. Источники
- [ ] [src/AGENTS.md - Module Structure](../src/AGENTS.md)
- [ ] [Примеры UseCase в других модулях](../src/Module/User/Application/UseCase/)

## 9. Комментарии
Application слой не должен содержать инфраструктурных деталей. Все внешние зависимости — через интерфейсы.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-13 | backend_developer | Выполнение задачи (PR #2099) |
