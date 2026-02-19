# TASK-status-health-infra-minio: MinIO health checker

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
- **Ветка**: task/TASK-status-health-infra-minio
- **PR**: —
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть доступность MinIO,
чтобы понимать работоспособность объектного хранилища.

### Цель (SMART)
Реализовать Infrastructure компонент для проверки здоровья MinIO:
1) Component с интерфейсом для выполнения health check;
2) Service для интеграции с HealthCheckerRegistry;
3) Проверка через S3 API (HeadBucket или HeadObject).

## 2. Контекст и Границы (Scope)
**Где делаем:** `src/Module/Health/Infrastructure/Component/MinioHealthCheck/`

**Границы (Out of Scope):**
- Проверка размера bucket
- Проверка политики доступа
- Метрики storage usage

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] Interface `MinioHealthCheckComponentInterface` с методом `check(): HealthCheckResult`
- [x] Class `MinioHealthCheckComponent` реализующий интерфейс
- [x] Service `MinioHealthCheckerService` для registry
- [x] Проверка через S3 client (HeadBucket или listBuckets)
- [x] Обработка исключений (connection refused, auth failure, bucket not found)
- [x] Integration тест с реальным MinIO

### 🟡 Should Have
- [ ] Проверка health endpoint MinIO (/minio/health/live)
- [ ] Возврат версии MinIO server

### 🟢 Could Have
- [ ] Проверка disk usage

### ⚫ Won't Have
- [ ] Управление bucket policies

## 4. План реализации (Tasks)
1. [x] Создать `MinioHealthCheckComponentInterface.php`
2. [x] Создать `MinioHealthCheckComponent.php` с DI для S3 client
3. [x] Создать `MinioHealthCheckerService.php`
4. [x] Добавить конфигурацию в `services.yaml`
5. [x] Написать Integration тест
6. [x] Зарегистрировать в HealthCheckerRegistryService

## 5. Критерии приемки (Definition of Ready)
- [x] Component использует существующий S3 client
- [x] Корректно обрабатывает ошибки подключения
- [x] Integration тест проходит
- [x] Зарегистрирован в DI контейнере

## 6. Самопроверка (Verification)
```bash
make tests-integration
make check
```

## 7. Риски и Зависимости
- MinIO должен быть доступен
- S3 client должен быть настроен

## 8. Источники
- [ ] [MinIO Health Check](https://min.io/docs/minio/linux/operations/monitoring/healthcheck-probe.html)

## 9. Комментарии
В проде MinIO работает в контейнере — health check должен учитывать это.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-14 | backend_developer | Выполнена задача: созданы Component, Service, конфигурация DI, Integration тесты |
