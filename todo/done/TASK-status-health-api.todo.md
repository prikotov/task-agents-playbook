# TASK-status-health-api: Health API endpoints

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
- **Ветка**: task/TASK-status-health-api
- **PR**: #2106
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы или внешний мониторинг,
я хочу иметь health API endpoints,
чтобы определять готовность приложения к работе.

### Цель (SMART)
Реализовать Health API endpoints в apps/api:
1) `GET /health` — liveness probe (200 OK или 503);
2) `GET /health/ready` — readiness probe с детальным статусом зависимостей;
3) JSON response с статусом системы.

## 2. Контекст и Границы (Scope)
**Где делаем:** `apps/api/src/v1/Module/Health/`

**Текущее поведение:** Уже есть базовый `HealthController` в `apps/api/src/v1/Controller/` с простым `{'status': 'ok'}`.

**Границы (Out of Scope):**
- Аутентификация для health endpoints (public)
- Детальные метрики
- Dashboard UI

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] Route класс `HealthRoute` с константами путей
- [x] Controller `LivenessController` для `GET /health`
- [x] Controller `ReadinessController` для `GET /health/ready`
- [x] Response 200 OK если система жива
- [x] Response 503 Service Unavailable если критический сервис недоступен
- [x] JSON response format: `{status: operational|degraded|outage, services: [...]}`
- [x] E2E тесты для endpoints

### 🟡 Should Have
- [x] Cache-control headers для предотвращения частых запросов
- [x] Response time в body

### 🟢 Could Have
- [ ] Поддержка формата Prometheus

### ⚫ Won't Have
- [x] Аутентификация

## 4. План реализации (Tasks)
1. [x] Создать директорию `apps/api/src/v1/Module/Health/`
2. [x] Создать `HealthModule.php`
3. [x] Создать `Controller/LivenessController.php` (расширить функционал существующего `/health`)
4. [x] Создать `Controller/ReadinessController.php` для `GET /health/ready`
5. [x] Создать `Resource/config/services.yaml`
6. [x] Обновить конфигурацию routes
7. [x] Написать Integration тесты

## 5. Критерии приемки (Definition of Ready)
- [x] Endpoints публично доступны (без auth)
- [x] Корректные HTTP status codes
- [x] JSON response валиден
- [x] Integration тесты проходят

## 6. Самопроверка (Verification)
```bash
make tests-e2e-api
make check
```

## 7. Риски и Зависимости
- Зависит от TASK-status-health-application

## 8. Источники
- [x] [apps/api структура](../../apps/api/)

## 9. Комментарии
Endpoints должны быть максимально лёгкими и быстрыми.

Endpoints могут использоваться для внешних мониторингов (UptimeRobot, Pingdom) или ручной проверки.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-12 | system_analyst | Убрано упоминание Traefik/K8s (нет в проде) |
| 2026-02-12 | system_analyst | Исправлен путь: `apps/api/src/Module/` → `apps/api/src/v1/Module/` |
| 2026-02-12 | system_analyst | Учтён существующий HealthController.php |
| 2026-02-14 | backend_developer | Выполнена задача (PR #2106) |
