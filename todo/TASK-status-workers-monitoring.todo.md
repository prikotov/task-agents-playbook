# TASK-status-workers-monitoring: Workers monitoring health checker

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Ценность**: V4
- **Сложность**: C3
- **Приоритет**: P2
- **Зависит от**: TASK-status-health-application
- **Epic**: [EPIC-status-page](EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: —
- **Ветка**: task/TASK-status-workers-monitoring
- **PR**: —
- **Статус**: todo

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть состояние Symfony Messenger workers,
чтобы понимать обрабатываются ли фоновые задачи.

### Цель (SMART)
Реализовать Infrastructure компонент для проверки здоровья Workers:
1) Component с интерфейсом для выполнения health check;
2) Service для интеграции с HealthCheckerRegistry;
3) Проверка: workers running + queues not stuck.

## 2. Контекст и Границы (Scope)
**Где делаем:** `src/Module/Health/Infrastructure/Component/HealthCheck/Worker/`

**Текущее поведение:** Workers запускаются через Symfony Messenger, но их состояние не отслеживается.

**Границы (Out of Scope):**
- Автоматический перезапуск workers
- Детальные метрики производительности
- Мониторинг конкретных сообщений

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [ ] Interface `WorkersHealthCheckComponentInterface` с методом `check(): HealthCheckResult`
- [ ] Class `WorkersHealthCheckComponent` реализующий интерфейс
- [ ] Service `WorkersHealthCheckerService` для registry
- [ ] Проверка: хотя бы один worker запущен
- [ ] Обработка: no workers running
- [ ] Integration тест

### 🟡 Should Have
- [ ] Проверка количества workers
- [ ] Проверка размера очередей (не превышают лимит)
- [ ] Graceful degradation при отсутствии workers

### 🟢 Could Have
- [ ] Информация о потреблении памяти workers
- [ ] Проверка stale workers (зависшие)

### ⚫ Won't Have
- [ ] Автоматический перезапуск

## 4. План реализации (Tasks)
1. [ ] Создать `WorkersHealthCheckComponentInterface.php`
2. [ ] Создать `WorkersHealthCheckComponent.php`
3. [ ] Реализовать проверку через `ps` или Doctrine (messenger_messages)
4. [ ] Создать `WorkersHealthCheckerService.php`
5. [ ] Добавить конфигурацию в `services.yaml`
6. [ ] Написать Integration тест
7. [ ] Зарегистрировать в HealthCheckerRegistryService

## 5. Критерии приемки (Definition of Ready)
- [ ] Корректно определяет запущенные workers
- [ ] Обрабатывает отсутствие workers
- [ ] Integration тест проходит

## 6. Самопроверка (Verification)
```bash
make tests-integration
make check
```

## 7. Риски и Зависимости
- Symfony Messenger должен быть настроен
- Workers могут быть запущены через Supervisor

## 8. Источники
- [ ] [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- [ ] Supervisor integration

## 9. Комментарии
Проверка может осуществляться через анализ messenger_messages table или Supervisor API.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
