# TASK-status-cli-ytdlp: yt-dlp health checker (Integration Layer)

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-12
- **Ценность**: V3
- **Сложность**: C2
- **Приоритет**: P1
- **Зависит от**: TASK-status-health-application
- **Epic**: [EPIC-status-page](EPIC-status-page.todo.md)
- **Автор**: system_analyst
- **Исполнитель**: backend_developer
- **Ветка**: task/TASK-status-cli-ytdlp
- **PR**: #2110
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть работоспособность yt-dlp,
чтобы понимать доступность функции скачивания видео/аудио.

### Цель (SMART)
Реализовать health check для yt-dlp через Integration слой:
1) Query UseCase в Source Module для проверки здоровья;
2) Integration Service в Health Module (вызывает Query через QueryBus);
3) Переиспользование существующего YtDlpComponent.

## 2. Контекст и Границы (Scope)
**Где делаем:**
- Source Module: `src/Module/Source/Application/UseCase/Query/CheckYtDlpHealth/`
- Health Module: `src/Module/Health/Integration/Service/HealthChecker/`

**Архитектурный контекст (ADR-001):**
- Integration слой вызывает Source Module через QueryBus
- Переиспользуется существующий YtDlpComponent
- Результат сохраняется в БД через cron на Worker Server

**Границы (Out of Scope):**
- Реальное скачивание видео
- Проверка всех поддерживаемых платформ
- Метрики скорости скачивания
- Console команда (отдельная задача TASK-status-cli-console)

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `YtDlpHealthDto` с полями: isHealthy, version, errorMessage
- [x] Query `CheckYtDlpHealthQuery` в Source Module
- [x] QueryHandler `CheckYtDlpHealthQueryHandler` использующий YtDlpComponent
- [x] Interface `CheckHealthServiceInterface` в Health/Domain/Service (переиспользован)
- [x] Service `YtDlpHealthCheckerService` в Health/Integration/Service через QueryBus
- [x] Unit тесты для QueryHandler
- [x] Integration тест для Service

### 🟡 Should Have
- [ ] Проверка возможности скачивания через simulate mode
- [x] Возврат версии yt-dlp в HealthCheckResultVo

### 🟢 Could Have
- [ ] Проверка обновлений yt-dlp

### ⚫ Won't Have
- [x] Реальное скачивание контента
- [x] Прямой вызов YtDlpComponent из Health Module (нарушение конвенции)

## 4. План реализации (Tasks)

### Phase 1: Source Module Query UseCase
1. [x] Создать `YtDlpHealthDto.php` в Source/Application/Dto
2. [x] Создать `CheckYtDlpHealthQuery.php` в Source/Application/UseCase/Query/CheckYtDlpHealth
3. [x] Создать `CheckYtDlpHealthQueryHandler.php` использующий YtDlpComponent
4. [x] Написать Unit тест для QueryHandler

### Phase 2: Health Module Integration Service
5. [x] Переиспользовать `CheckHealthServiceInterface` из Health/Domain/Service/HealthChecker
6. [x] Создать `YtDlpHealthCheckerService.php` в Health/Integration/Service/HealthChecker
7. [x] Обновить DI конфигурацию (tag health.checker)
8. [x] Написать Integration тест

### Phase 3: Cleanup
9. [x] Старый `YtDlpHealthCheckComponent` отсутствует — не требуется удаление
10. [x] services.yaml обновлён

## 5. Структура файлов

```
src/Module/Source/
├── Application/
│   ├── Dto/
│   │   └── YtDlpHealthDto.php                    # новый
│   └── UseCase/
│       └── Query/
│           └── CheckYtDlpHealth/
│               ├── CheckYtDlpHealthQuery.php       # новый
│               └── CheckYtDlpHealthQueryHandler.php # новый

src/Module/Health/
├── Domain/
│   └── Service/
│       └── HealthChecker/
│           └── CheckYtDlpHealthServiceInterface.php  # новый
└── Integration/
    └── Service/
        └── HealthChecker/
            └── CheckYtDlpHealthService.php           # новый
```

## 6. Критерии приемки (Definition of Ready)
- [x] QueryHandler корректно использует YtDlpComponent
- [x] Integration Service вызывает Query через QueryBus
- [x] Результат маппится в HealthCheckResultVo
- [x] Unit и Integration тесты проходят
- [x] DI конфигурация корректна

## 7. Самопроверка (Verification)
```bash
make tests-unit
make tests-integration
make check
```

## 8. Риски и Зависимости
- yt-dlp должен быть установлен в системе
- QueryBus должен быть настроен для cross-module communication

## 9. Источники
- [x] [ADR-001 в EPIC-status-page](EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [x] [YtDlpComponent](../src/Module/Source/Infrastructure/Component/YtDlp/)
- [x] [yt-dlp](https://github.com/yt-dlp/yt-dlp)
- [x] [Конвенция Application Layer](../docs/conventions/layers/application.md)
- [x] [Конвенция Service Naming](../docs/conventions/core_patterns/service.md)

## 10. Комментарии
Подход переиспользования YtDlpComponent через QueryBus соответствует конвенции project layers.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-14 | backend_developer | Реализация: interface, component, service, tests, config |
| 2026-02-14 | backend_developer | **Редизайн (ADR-001):** переход на Integration слой через QueryBus вместо прямого Component в Health Module |
| 2026-02-15 | backend_developer | Добавлены Unit тесты для CheckYtDlpHealthQueryHandler, задача завершена |
