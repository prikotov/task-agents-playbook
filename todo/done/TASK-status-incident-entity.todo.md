# TASK-status-incident-entity: Incident entity и Value Objects

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Дата обновления**: 2026-02-17
- **Ценность**: V4
- **Сложность**: C2
- **Приоритет**: P2
- **Зависит от**: TASK-status-health-domain
- **Epic**: [EPIC-status-page](../EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: backend_developer
- **Ветка**: task/TASK-status-incident-entity
- **PR**: #2124
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу создавать и отслеживать инциденты,
чтобы информировать пользователей о проблемах и их решении.

### Цель (SMART)
Реализовать Domain слой для управления инцидентами:
1) Entity `IncidentModel` с полной инкапсуляцией бизнес-логики;
2) Value Objects: `IncidentTitleVo`, `IncidentDescriptionVo`;
3) Enum: `IncidentStatusEnum`, `IncidentSeverityEnum`;
4) Repository interface `IncidentRepositoryInterface` с Criteria.

## 2. Контекст и Границы (Scope)
**Где делаем:** 
- `src/Module/Health/Domain/Entity/IncidentModel.php`
- `src/Module/Health/Domain/ValueObject/IncidentTitleVo.php`
- `src/Module/Health/Domain/ValueObject/IncidentDescriptionVo.php`
- `src/Module/Health/Domain/Enum/IncidentStatusEnum.php`
- `src/Module/Health/Domain/Enum/IncidentSeverityEnum.php`
- `src/Module/Health/Domain/Repository/Incident/`

**Текущее поведение:** Инциденты не отслеживаются.

**Границы (Out of Scope):**
- Infrastructure реализация (TASK-status-incident-storage)
- Application layer (UseCases, Commands, Queries)
- Integration слой
- UI

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] Entity `IncidentModel` с полями:
  - uuid (Uuid)
  - title (string, через IncidentTitleVo)
  - description (string, через IncidentDescriptionVo)
  - status (IncidentStatusEnum)
  - severity (IncidentSeverityEnum)
  - affectedServiceNames (array of ServiceNameVo)
  - createdAt (DateTimeImmutable)
  - updatedAt (DateTimeImmutable)
  - resolvedAt (DateTimeImmutable|null)
- [x] ValueObject `IncidentTitleVo` (final readonly, string с валидацией 3-255 chars)
- [x] ValueObject `IncidentDescriptionVo` (final readonly, string max 10000 chars)
- [x] Enum `IncidentStatusEnum`: investigating, identified, monitoring, resolved
- [x] Enum `IncidentSeverityEnum`: minor, major, critical
- [x] Repository interface `IncidentRepositoryInterface` (по аналогии с ServiceStatusRepositoryInterface)
- [x] Criteria interface `IncidentCriteriaInterface`
- [x] Criteria `IncidentFindCriteria` для поиска
- [x] Unit тесты для Entity, VO, Enum (>= 80% coverage)

### 🟡 Should Have
- [x] Method `IncidentModel::resolve()` с установкой resolvedAt
- [x] Method `IncidentModel::updateStatus(IncidentStatusEnum $status)` с обновлением updatedAt
- [x] Method `IncidentModel::addAffectedService(ServiceNameVo $serviceName)`
- [x] Method `IncidentModel::removeAffectedService(ServiceNameVo $serviceName)`
- [ ] Domain events: `IncidentCreatedEvent`, `IncidentResolvedEvent`, `IncidentStatusChangedEvent` (отложено до Application слоя)

### 🟢 Could Have
- [ ] ValueObject `IncidentTimelineVo` для истории изменений (отложить)
- [ ] Specification `ActiveIncidentSpecification` для фильтрации

### ⚫ Won't Have
- [ ] Infrastructure реализация (в TASK-status-incident-storage)
- [ ] Application UseCases (в отдельной задаче)
- [ ] UI (в TASK-status-incident-ui)

## 4. План реализации (Tasks)
### Domain/Entity
- [x] Создать `src/Module/Health/Domain/Entity/IncidentModel.php`
   - Использовать traits: IdTrait, UuidTrait, InsTsTrait, UpdTsTrait
   - Реализовать interfaces: IdModelInterface, UuidModelInterface, InsTsModelInterface, UpdTsModelInterface
   - Factory method `create()`, методы `resolve()`, `updateStatus()`

### Domain/ValueObject
- [x] Создать `src/Module/Health/Domain/ValueObject/IncidentTitleVo.php` (final readonly)
- [x] Создать `src/Module/Health/Domain/ValueObject/IncidentDescriptionVo.php` (final readonly)

### Domain/Enum
- [x] Создать `src/Module/Health/Domain/Enum/IncidentStatusEnum.php`
- [x] Создать `src/Module/Health/Domain/Enum/IncidentSeverityEnum.php`

### Domain/Repository
- [x] Создать `src/Module/Health/Domain/Repository/Incident/IncidentCriteriaInterface.php`
- [x] Создать `src/Module/Health/Domain/Repository/Incident/IncidentRepositoryInterface.php`
- [x] Создать `src/Module/Health/Domain/Repository/Incident/Criteria/IncidentFindCriteria.php`

### Tests
- [x] Создать `tests/Unit/Module/Health/Domain/Entity/IncidentModelTest.php`
- [x] Создать `tests/Unit/Module/Health/Domain/ValueObject/IncidentTitleVoTest.php`
- [x] Создать `tests/Unit/Module/Health/Domain/ValueObject/IncidentDescriptionVoTest.php`

## 5. Критерии приемки (Definition of Ready)
- [x] Entity инкапсулирует всю бизнес-логику инцидента
- [x] Все Value Objects `final readonly` с постфиксом `Vo`
- [x] Entity имеет постфикс `Model`
- [x] Entity не имеет зависимостей на другие слои (Domain isolation)
- [x] Repository interface следует конвенции ServiceStatusRepositoryInterface
- [x] Unit тесты покрывают >= 80% кода
- [x] PHPStan level 5 проходит без ошибок

## 6. Самопроверка (Verification)
```bash
make tests-unit
make check
```

## 7. Риски и Зависимости
- Зависит от существующего ServiceNameVo (ValueObject из Health/Domain)
- Зависит от Common traits (IdTrait, UuidTrait, InsTsTrait, UpdTsTrait)
- Incident ссылается на ServiceStatusModel по имени (ServiceNameVo), не по ID

## 8. Источники
- [x] [src/AGENTS.md](../../src/AGENTS.md) - Module Structure
- [x] [docs/conventions/layers/domain/entity.md](../../docs/conventions/layers/domain/entity.md) - Entity naming conventions
- [x] [docs/conventions/core_patterns/value-object.md](../../docs/conventions/core_patterns/value-object.md) - VO naming conventions
- [x] [src/Module/Health/Domain/Entity/ServiceStatusModel.php](../../src/Module/Health/Domain/Entity/ServiceStatusModel.php) - пример Entity
- [x] [src/Module/Health/Domain/Repository/ServiceStatus/](../../src/Module/Health/Domain/Repository/ServiceStatus/) - пример Repository interface

## 9. Комментарии
Инцидент может затрагивать несколько сервисов (affectedServiceNames). Используется ServiceNameVo, а не ComponentId, для связи с ServiceStatusModel.

**Правила именования:**
- Entity → постфикс `Model` (IncidentModel)
- Value Object → постфикс `Vo` (IncidentTitleVo, IncidentDescriptionVo)
- Enum → постфикс `Enum` (IncidentStatusEnum, IncidentSeverityEnum)

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-16 | system_analyst | Детализация по конвенциям: добавлены Criteria, уточнены пути, добавлены методы Entity |
| 2026-02-16 | system_analyst | Исправлены имена классов по конвенциям: Entity→IncidentModel, VO→IncidentTitleVo/IncidentDescriptionVo |
| 2026-02-17 | backend_developer | Реализация Domain слоя: Entity, VO, Enum, Repository interface, Unit тесты (PR #2124) |
