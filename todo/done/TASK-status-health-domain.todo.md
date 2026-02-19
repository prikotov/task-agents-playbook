# TASK-status-health-domain: Создать Domain слой HealthModule

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Ценность**: V3
- **Сложность**: C2
- **Приоритет**: P1
- **Зависит от**: —
- **Epic**: [EPIC-status-page](../EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: backend_developer
- **Ветка**: task/TASK-status-health-domain
- **PR**: https://github.com/prikotov/TasK/pull/2092
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как разработчик,
я хочу иметь доменную модель для мониторинга здоровья сервисов,
чтобы единообразно представлять статус всех компонентов системы.

### Цель (SMART)
Создать Domain слой модуля Health с:
1) Entity `ServiceStatus` для хранения статуса сервиса;
2) ValueObject для типизации данных;
3) Enum для классификации сервисов и статусов;
4) Repository interface для persistence.

## 2. Контекст и Границы (Scope)
**Где делаем:** `src/Module/Health/Domain/`

**Текущее поведение:** Модуль Health не существует.

**Границы (Out of Scope):**
- Application слой (UseCases, DTO)
- Infrastructure реализации (Repository, Component)
- Integration слой

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] Entity `ServiceStatus` с полями: uuid, name, category, status, lastCheckAt, message
- [x] ValueObject `ServiceName` для валидации имени сервиса
- [x] Enum `ServiceStatusEnum`: operational, degraded, outage, maintenance
- [x] Enum `ServiceCategoryEnum`: infrastructure, llm, external_api, cli_tool
- [x] ValueObject `HealthCheckResult` для результата проверки
- [x] Repository interface `ServiceStatusRepositoryInterface`
- [x] Unit тесты для Entity и ValueObject

### 🟡 Should Have
- [ ] ValueObject `ServiceId` (Uuid wrapper)
- [ ] Specification для проверки критических сервисов

### 🟢 Could Have
- [ ] Domain events: ServiceStatusChangedEvent

### ⚫ Won't Have
- [ ] Infrastructure реализации

## 4. План реализации (Tasks)
1. [x] Создать директорию `src/Module/Health/Domain/`
2. [x] Создать `ServiceStatusEnum.php`
3. [x] Создать `ServiceCategoryEnum.php`
4. [x] Создать ValueObject `ServiceName.php`
5. [x] Создать ValueObject `HealthCheckResult.php`
6. [x] Создать Entity `ServiceStatusModel.php`
7. [x] Создать Repository interface `ServiceStatusRepositoryInterface.php`
8. [x] Написать Unit тесты

## 5. Критерии приемки (Definition of Ready)
- [x] Все классы соответствуют DDD принципам (no external dependencies)
- [x] Все ValueObject `final readonly`
- [x] Unit тесты покрывают >= 80% кода
- [x] PHPStan level проходит без ошибок

## 6. Самопроверка (Verification)
```bash
make tests-unit
make check
```

## 7. Риски и Зависимости
- Нет зависимостей от других модулей

## 8. Источники
- [x] [src/AGENTS.md - Module Structure](../src/AGENTS.md)
- [x] [Примеры Entity в других модулях](../src/Module/User/Domain/Entity/)

## 9. Комментарии
Фундаментальная задача для всего эпика. От качества Domain слоя зависит вся дальнейшая реализация.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-12 | backend_developer | Выполнение задачи, создание PR #2092 |
