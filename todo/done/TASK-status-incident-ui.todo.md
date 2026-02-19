# TASK-status-incident-ui: Incident management UI

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Дата обновления**: 2026-02-17
- **Ценность**: V4
- **Сложность**: C3
- **Приоритет**: P2
- **Зависит от**: TASK-status-incident-storage, TASK-status-public-page
- **Epic**: [EPIC-status-page](../EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: backend_developer
- **Ветка**: task/TASK-status-incident-ui
- **PR**: #2126
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу создавать и управлять инцидентами через UI,
чтобы информировать пользователей о проблемах.

### Цель (SMART)
Реализовать UI для управления инцидентами в apps/web:
1) Application слой: Commands, Queries, DTO для инцидентов;
2) Admin CRUD интерфейс для управления инцидентами;
3) Отображение активных инцидентов на публичной статус-странице;
4) E2E тесты.

## 2. Контекст и Границы (Scope)
**Где делаем:** 
- Application: `src/Module/Health/Application/UseCase/Command/Incident*/`, `src/Module/Health/Application/UseCase/Query/Incident*/`
- Admin UI: `apps/web/src/Module/Health/Controller/Admin/IncidentController.php`
- Templates: `apps/web/src/Module/Health/Resource/templates/admin/incident/`
- Status page update: `apps/web/src/Module/Health/Resource/templates/status/index.html.twig`

**Текущее поведение:** Инциденты не отображаются на статус-странице, нет UI управления.

**Границы (Out of Scope):**
- Автоматическое создание инцидентов
- Email уведомления
- Webhook уведомления

## 3. Требования (MoSCoW)
### 🔴 Must Have

#### Application Layer
- [x] DTO `IncidentDto` (final readonly)
- [x] DTO `IncidentListDto` (final readonly)
- [x] Service `IncidentDtoMapper` для преобразования Model → DTO
- [x] Command `CreateIncidentCommand` + `CreateIncidentCommandHandler`
- [x] Command `UpdateIncidentCommand` + `UpdateIncidentCommandHandler`
- [x] Command `ResolveIncidentCommand` + `ResolveIncidentCommandHandler`
- [x] Command `DeleteIncidentCommand` + `DeleteIncidentCommandHandler`
- [x] Query `GetIncidentListQuery` + `GetIncidentListQueryHandler`
- [x] Query `GetIncidentQuery` + `GetIncidentQueryHandler`
- [x] Unit тесты для Command/Query handlers

#### Admin UI
- [x] Controller `ListController` с CRUD действиями (list, new, edit, delete, resolve)
- [x] Route `/admin/incidents/*` 
- [x] Template `admin/incident/index.html.twig` - список инцидентов с пагинацией
- [x] Template `admin/incident/new.html.twig` - форма создания инцидента
- [x] Template `admin/incident/edit.html.twig` - форма редактирования инцидента
- [x] Flash messages для подтверждения действий

#### Status Page Integration
- [x] Блок "Active Incidents" на публичной статус-странице
- [x] Отображение severity цветовой индикацией

### 🟡 Should Have
- [x] Фильтрация по status и severity
- [x] Сортировка по created_at (insTs)

### 🟢 Could Have
- [ ] Template `admin/incident/show.html.twig` - детали инцидента
- [ ] Timeline изменений инцидента (history)
- [ ] Markdown поддержка для description
- [ ] Подсветка affected services на статус-странице

### ⚫ Won't Have
- [ ] Автоматическое создание инцидентов
- [ ] Webhook уведомления
- [ ] Email уведомления

## 4. План реализации (Tasks)
### Application Layer (src/Module/Health/Application/)
1. [x] Создать `Dto/IncidentDto.php` (final readonly)
2. [x] Создать `Dto/IncidentListDto.php` (final readonly)
3. [x] Создать `Service/IncidentDtoMapper.php`
4. [x] Создать `UseCase/Command/Incident/CreateIncidentCommand.php`
5. [x] Создать `UseCase/Command/Incident/CreateIncidentCommandHandler.php`
6. [x] Создать `UseCase/Command/Incident/UpdateIncidentCommand.php`
7. [x] Создать `UseCase/Command/Incident/UpdateIncidentCommandHandler.php`
8. [x] Создать `UseCase/Command/Incident/ResolveIncidentCommand.php`
9. [x] Создать `UseCase/Command/Incident/ResolveIncidentCommandHandler.php`
10. [x] Создать `UseCase/Command/Incident/DeleteIncidentCommand.php`
11. [x] Создать `UseCase/Command/Incident/DeleteIncidentCommandHandler.php`
12. [x] Создать `UseCase/Query/Incident/GetIncidentListQuery.php`
13. [x] Создать `UseCase/Query/Incident/GetIncidentListQueryHandler.php`
14. [x] Создать `UseCase/Query/Incident/GetIncidentQuery.php`
15. [x] Создать `UseCase/Query/Incident/GetIncidentQueryHandler.php`
16. [x] Написать Unit тесты для handlers

### Admin UI (apps/web/src/Module/Health/)
17. [x] Создать `Controller/Admin/Incident/ListController.php`
18. [x] Создать `Route/IncidentRoute.php`
19. [x] Обновить `Resource/config/services.yaml`
20. [x] Создать `Resource/templates/admin/incident/index.html.twig`
21. [x] Создать `Resource/templates/admin/incident/new.html.twig`
22. [x] Создать `Resource/templates/admin/incident/edit.html.twig`

### Status Page Integration
23. [x] Обновить `Resource/templates/status/index.html.twig` для отображения активных инцидентов
24. [x] Добавить Query для GetIncidentList в статус-контроллер

### Tests
25. [x] Создать `tests/Unit/Module/Health/Application/UseCase/Command/Incident/CreateIncidentCommandHandlerTest.php`
26. [x] Создать `tests/Unit/Module/Health/Application/UseCase/Query/Incident/GetIncidentListQueryHandlerTest.php`
27. [ ] Создать E2E тесты для admin CRUD

## 5. Критерии приемки (Definition of Ready)
- [x] Оператор может создать инцидент через `/admin/incidents/new`
- [x] Оператор может редактировать инцидент через `/admin/incidents/{uuid}/edit`
- [x] Оператор может разрешить инцидент через `/admin/incidents/{uuid}/resolve`
- [x] Активные инциденты отображаются на публичной статус-странице `/status`
- [x] UI соответствует Bootstrap 5 Phoenix теме
- [x] Unit тесты покрывают Application layer
- [ ] E2E тесты (отложено)

## 6. Самопроверка (Verification)
```bash
make tests-unit     # ✅ 1682 tests OK
make check          # ✅ All checks passed
```

## 7. Риски и Зависимости
- [x] Зависит от Incident storage (TASK-status-incident-storage) — PR #2125 merged
- [x] Зависит от Status Page (TASK-status-public-page) — уже реализовано
- Требует авторизации через существующую систему аутентификации

## 8. Источники
- [x] [src/AGENTS.md](../../src/AGENTS.md) - Application layer structure
- [x] [docs/conventions/core_patterns/dto.md](../../docs/conventions/core_patterns/dto.md) - DTO naming conventions
- [x] [Bootstrap 5 Phoenix](../../docs/theme/)
- [x] [apps/web/AGENTS.md](../../apps/web/AGENTS.md)

## 9. Комментарии
Требует авторизации через существующую систему аутентификации. Admin routes должны быть защищены middleware.

Инциденты на статус-странице отображаются:
- В блоке "Active Incidents" над списком компонентов
- Цветовая индикация: critical (red), major (orange), minor (yellow)
- При resolved инцидент перемещается в историю (отдельная задача)

**Правила именования:**
- DTO → постфикс `Dto` (IncidentDto)
- Command → постфикс `Command` (CreateIncidentCommand)
- Query → постфикс `Query` (GetIncidentListQuery)
- Handler → постфикс `Handler` (CreateIncidentCommandHandler)

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-12 | system_analyst | Исправлены пути согласно структуре apps/web/src/Module/ |
| 2026-02-16 | system_analyst | Детализация: добавлен Application layer (Commands, Queries, DTO), уточнены пути |
| 2026-02-16 | system_analyst | Добавлены ссылки на конвенции именования DTO, Command, Query, Handler |
| 2026-02-17 | backend_developer | Реализованы все Must Have требования, создан PR #2126 |
