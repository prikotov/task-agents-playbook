# TASK-status-health-infra-rabbitmq: RabbitMQ health checker

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Ценность**: V3
- **Сложность**: C2
- **Приоритет**: P1
- **Зависит от**: TASK-status-health-application
- **Epic**: [EPIC-status-page](EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: —
- **Ветка**: task/TASK-status-health-infra-rabbitmq
- **PR**: —
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть доступность RabbitMQ,
чтобы понимать работоспособность очередей сообщений.

### Цель (SMART)
Реализовать Infrastructure компонент для проверки здоровья RabbitMQ:
1) Component с интерфейсом для выполнения health check;
2) Service для интеграции с HealthCheckerRegistry;
3) Проверка через AMQP connection + Management API (опционально).

## 2. Контекст и Границы (Scope)
**Где делаем:** `src/Module/Health/Infrastructure/Component/HealthCheck/Infrastructure/`

**Границы (Out of Scope):**
- Проверка отдельных очередей (отдельная задача TASK-status-workers-monitoring)
- Проверка consumers
- Метрики throughput

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] Interface `RabbitMqHealthCheckComponentInterface` с методом `check(): HealthCheckResult`
- [x] Class `RabbitMqHealthCheckComponent` реализующий интерфейс
- [x] Service `RabbitMqHealthCheckerService` для registry
- [x] Проверка AMQP connection (connect/disconnect)
- [x] Обработка исключений (connection refused, auth failure)
- [x] Integration тест с реальным RabbitMQ

### 🟡 Should Have
- [ ] Проверка Management API доступности
- [ ] Возврат версии RabbitMQ server

### 🟢 Could Have
- [ ] Количество открытых connections

### ⚫ Won't Have
- [ ] Метрики очередей

## 4. План реализации (Tasks)
1. [x] Создать `RabbitMqHealthCheckComponentInterface.php`
2. [x] Создать `RabbitMqHealthCheckComponent.php` с DI для AMQP connection
3. [x] Создать `RabbitMqHealthCheckerService.php`
4. [x] Добавить конфигурацию в `services.yaml`
5. [x] Написать Integration тест
6. [x] Зарегистрировать в HealthCheckerRegistryService

## 5. Критерии приемки (Definition of Ready)
- [x] Component использует существующий AMQP connection
- [x] Корректно обрабатывает ошибки подключения
- [x] Integration тест проходит
- [x] Зарегистрирован в DI контейнере

## 6. Самопроверка (Verification)
```bash
make tests-integration
make check
```

## 7. Риски и Зависимости
- RabbitMQ должен быть доступен
- AMQP connection должен быть настроен

## 8. Источники
- [ ] [RabbitMQ AMQP](https://www.rabbitmq.com/)

## 9. Комментарии
Использовать существующую конфигурацию RabbitMQ из проекта.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-13 | backend_developer | Реализация RabbitMQ health checker (PR #2103) |
