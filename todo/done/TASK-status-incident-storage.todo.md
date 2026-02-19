# TASK-status-incident-storage: Incident repository и persistence

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Дата обновления**: 2026-02-17
- **Ценность**: V4
- **Сложность**: C2
- **Приоритет**: P2
- **Зависит от**: TASK-status-incident-entity
- **Epic**: [EPIC-status-page](../EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: backend_developer
- **Ветка**: task/TASK-status-incident-storage
- **PR**: #2125
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу чтобы инциденты сохранялись в базе данных,
чтобы иметь историю инцидентов и их решений.

### Цель (SMART)
Реализовать Infrastructure слой для хранения инцидентов:
1) Doctrine Repository implementation;
2) Criteria Mapper для фильтрации;
3) Database migration для таблицы `health_incidents`.

## 2. Контекст и Границы (Scope)
**Где делаем:** 
- `src/Module/Health/Infrastructure/Repository/Incident/`
- `migrations/`

**Текущее поведение:** Инциденты не сохраняются.

**Границы (Out of Scope):**
- Application layer (UseCases, Commands, Queries)
- UI
- API endpoints

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] Repository `IncidentRepository` реализующий `IncidentRepositoryInterface`
- [x] Criteria Mapper `IncidentCriteriaMapper` для преобразования criteria в QueryBuilder
- [x] In-memory repository `InMemoryIncidentRepository` для тестов
- [x] Migration `Version20260217060535.php` для таблицы `health_incidents`
- [x] Integration тест для `IncidentRepository`
- [x] Индекс по `status` для быстрого поиска активных инцидентов
- [x] Индекс по `ins_ts` для сортировки

### 🟡 Should Have
- [x] Индекс по `severity` для фильтрации
- [x] JSON колонка для `affected_service_names`
- [ ] Soft delete возможность (deleted_at колонка)

### 🟢 Could Have
- [ ] Таблица `health_incident_timeline` для истории изменений

### ⚫ Won't Have
- [ ] Application services
- [ ] API endpoints

## 4. План реализации (Tasks)
### Infrastructure/Repository
1. [x] Создать `src/Module/Health/Infrastructure/Repository/Incident/IncidentRepository.php`
   - extends ServiceEntityRepository
   - implements IncidentRepositoryInterface
   - getById, getOneByCriteria, getByCriteria, getCountByCriteria, exists, save, delete

2. [x] Создать `src/Module/Health/Infrastructure/Repository/Incident/Criteria/CriteriaMapper.php`
   - Абстрактный маппер для критериев

3. [x] Создать `src/Module/Health/Infrastructure/Repository/Incident/Criteria/Mapper/IncidentFindCriteriaMapper.php`
   - Маппинг IncidentFindCriteria в QueryBuilder

4. [x] Создать `src/Module/Health/Infrastructure/Repository/Incident/InMemoryIncidentRepository.php`
   - In-memory реализация для unit-тестов

### Migration
5. [x] Создать migration `migrations/2026/02/Version20260217060535.php`
   - Таблица `health_incident`:
     - id (bigint, auto_increment)
     - uuid (uuid, unique)
     - title (varchar(255))
     - description (text, nullable)
     - status (varchar(20), enum)
     - severity (varchar(20), enum)
     - affected_service_names (json, nullable) - массив имён сервисов
     - ins_ts (timestamp)
     - upd_ts (timestamp, nullable)
     - resolved_at (timestamptz, nullable)
   - Индексы: idx_health_incident__status, idx_health_incident__ins_ts, idx_health_incident__severity

### Tests
6. [x] Создать `tests/Integration/Module/Health/Infrastructure/Repository/Incident/IncidentRepositoryTest.php`
   - save, delete, getByCriteria, getById сценарии

## 5. Критерии приемки (Definition of Ready)
- [x] Repository корректно сохраняет и извлекает инциденты
- [x] Criteria mapper работает для всех критериев поиска
- [x] Migration выполняется без ошибок (`make migrate`)
- [x] Integration тест проходит
- [x] In-memory repository доступен для unit-тестов

## 6. Самопроверка (Verification)
```bash
make migrate
make tests-integration-path tests/Integration/Module/Health/Infrastructure/Repository/Incident/
make check
```

## 7. Риски и Зависимости
- Зависит от IncidentModel entity (TASK-status-incident-entity) ✅
- Нужна миграция для новой таблицы ✅
- affected_service_names хранит массив строк (ServiceNameVo), не foreign keys ✅

## 8. Источники
- [x] [src/Module/Health/Infrastructure/Repository/ServiceStatus/](../src/Module/Health/Infrastructure/Repository/ServiceStatus/) - пример репозитория
- [x] [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)

## 9. Комментарии
Использовать PostgreSQL JSON для affected_service_names. Это позволяет хранить массив имён сервисов без foreign key связи.

Пример структуры JSON:
```json
["api-gateway", "llm-provider", "database"]
```

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-16 | system_analyst | Детализация по конвенциям: добавлены Criteria Mapper, InMemory repository, уточнена структура таблицы |
| 2026-02-17 | backend_developer | Реализованы IncidentRepository, CriteriaMapper, IncidentFindCriteriaMapper, InMemoryIncidentRepository, Migration, Integration tests |
