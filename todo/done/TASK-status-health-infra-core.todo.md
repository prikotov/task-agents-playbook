# TASK-status-health-infra-core: PostgreSQL health checker

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
- **Ветка**: task/TASK-status-health-infra-core
- **PR**: #2102
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть доступность PostgreSQL,
чтобы понимать работоспособность базы данных.

### Цель (SMART)
Реализовать Infrastructure компонент для проверки здоровья PostgreSQL:
1) Component с интерфейсом для выполнения health check;
2) Service для интеграции с HealthCheckerRegistry;
3) Проверка через Doctrine DBAL (SELECT 1).

## 2. Контекст и Границы (Scope)
**Где делаем:** `src/Module/Health/Infrastructure/Component/HealthCheck/Infrastructure/`

**Текущее поведение:** Нет health check для PostgreSQL.

**Границы (Out of Scope):**
- Проверка реплик
- Проверка миграций
- Метрики производительности БД

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] Interface `DatabaseHealthCheckComponentInterface` с методом `check(): HealthCheckResult`
- [x] Class `DatabaseHealthCheckComponent` реализующий интерфейс
- [x] Service `DatabaseHealthCheckerService` для registry
- [x] Проверка через Doctrine DBAL: `SELECT 1`
- [x] Обработка исключений (connection refused, timeout)
- [x] Integration тест с реальной БД

### 🟡 Should Have
- [x] Проверка latency запроса
- [x] Конфигурируемый timeout

### 🟢 Could Have
- [ ] Проверка размера connection pool

### ⚫ Won't Have
- [ ] Проверка реплик БД

## 4. План реализации (Tasks)
1. [x] Создать `DatabaseHealthCheckComponentInterface.php`
2. [x] Создать `DatabaseHealthCheckComponent.php` с DI для Doctrine DBAL
3. [x] Создать `DatabaseHealthCheckerService.php`
4. [x] Добавить конфигурацию в `services.yaml`
5. [x] Написать Integration тест
6. [x] Зарегистрировать в HealthCheckerRegistryService

## 5. Критерии приемки (Definition of Ready)
- [x] Component использует Doctrine DBAL
- [x] Корректно обрабатывает ошибки подключения
- [x] Integration тест проходит
- [x] Зарегистрирован в DI контейнере

## 6. Самопроверка (Verification)
```bash
make tests-integration
make check
```

## 7. Риски и Зависимости
- Doctrine DBAL должен быть настроен

## 8. Источники
- [ ] [Doctrine DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/current/)

## 9. Комментарии
Это первый health checker — шаблон для остальных Infrastructure checkers.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-13 | backend_developer | Реализация: DatabaseHealthCheckComponent, DatabaseHealthCheckerService, тесты (PR #2102) |
