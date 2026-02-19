# TASK-status-cli-whisper: whisper.cpp health checker (Integration Layer)

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
- **Ветка**: task/TASK-status-cli-whisper
- **PR**: #2111
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть работоспособность whisper.cpp,
чтобы понимать доступность функции транскрибации аудио.

### Цель (SMART)
Реализовать health check для whisper.cpp через Integration слой:
1) Query UseCase в SpeechToText Module для проверки здоровья;
2) Integration Service в Health Module (вызывает Query через QueryBus);
3) Переиспользование существующего WhisperCppCliComponent.

## 2. Контекст и Границы (Scope)
**Где делаем:**
- SpeechToText Module: `src/Module/SpeechToText/Application/UseCase/Query/CheckWhisperHealth/`
- Health Module: `src/Module/Health/Integration/Service/HealthChecker/`

**Архитектурный контекст (ADR-001):**
- Integration слой вызывает SpeechToText Module через QueryBus
- Переиспользуется существующий WhisperCppCliComponent
- Результат сохраняется в БД через cron на Worker Server

**Границы (Out of Scope):**
- Реальная транскрибация аудио
- Проверка всех моделей
- Метрики качества транскрибации
- Console команда (отдельная задача TASK-status-cli-console)

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `WhisperHealthDto` с полями: isHealthy, version, availableModels, errorMessage
- [x] Query `CheckWhisperHealthQuery` в SpeechToText Module
- [x] QueryHandler `CheckWhisperHealthQueryHandler` использующий WhisperHealthCheckComponent
- [x] Переиспользован `CheckHealthServiceInterface` из Health/Domain/Service
- [x] Service `WhisperHealthCheckerService` в Health/Integration/Service через QueryBus
- [x] Проверка: whisper-cli binary exists и executable
- [x] Проверка: хотя бы одна модель доступна в директории models/
- [x] Unit тесты для QueryHandler

### 🟡 Should Have
- [x] Возврат версии whisper.cpp
- [x] Список доступных моделей

### 🟢 Could Have
- [ ] Проверка GPU поддержки

### ⚫ Won't Have
- [x] Реальная транскрибация
- [x] Прямой вызов WhisperCppCliComponent из Health Module (нарушение конвенции)

## 4. План реализации (Tasks)

### Phase 1: SpeechToText Module Query UseCase
1. [x] Создать `WhisperHealthDto.php` в SpeechToText/Application/Dto
2. [x] Создать `CheckWhisperHealthQuery.php` в SpeechToText/Application/UseCase/Query/CheckWhisperHealth
3. [x] Создать `WhisperHealthCheckComponent.php` — новый компонент для проверки binary и моделей
4. [x] Создать `CheckWhisperHealthQueryHandler.php` использующий WhisperHealthCheckComponent
5. [x] Написать Unit тест для QueryHandler

### Phase 2: Health Module Integration Service
6. [x] Переиспользовать `CheckHealthServiceInterface` из Health/Domain/Service/HealthChecker
7. [x] Создать `WhisperHealthCheckerService.php` в Health/Integration/Service/HealthChecker
8. [x] Обновить DI конфигурацию (tag health.checker)

## 5. Структура файлов

```
src/Module/SpeechToText/
├── Application/
│   ├── Dto/
│   │   └── WhisperHealthDto.php                    # новый
│   └── UseCase/
│       └── Query/
│           └── CheckWhisperHealth/
│               ├── CheckWhisperHealthQuery.php       # новый
│               └── CheckWhisperHealthQueryHandler.php # новый

src/Module/Health/
├── Domain/
│   └── Service/
│       └── HealthChecker/
│           └── CheckWhisperHealthServiceInterface.php  # новый
└── Integration/
    └── Service/
        └── HealthChecker/
            └── CheckWhisperHealthService.php           # новый
```

## 6. Критерии приемки (Definition of Ready)
- [ ] QueryHandler корректно использует WhisperCppCliComponent
- [ ] Integration Service вызывает Query через QueryBus
- [ ] Результат маппится в HealthCheckResultVo
- [ ] Проверяет доступность моделей
- [ ] Unit и Integration тесты проходят
- [ ] DI конфигурация корректна

## 7. Самопроверка (Verification)
```bash
make tests-unit
make tests-integration
make check
```

## 8. Риски и Зависимости
- whisper.cpp должен быть установлен и скомпилирован
- Модели должны быть загружены
- QueryBus должен быть настроен для cross-module communication

## 9. Источники
- [ ] [ADR-001 в EPIC-status-page](EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [ ] [WhisperCppCliComponent](../src/Module/SpeechToText/Infrastructure/Component/WhisperCppCli/)
- [ ] [whisper.cpp](https://github.com/ggerganov/whisper.cpp)
- [ ] [Конвенция Application Layer](../docs/conventions/layers/application.md)

## 10. Комментарии
Путь к whisper.cpp и моделям должен быть конфигурируемым. Подход переиспользования WhisperCppCliComponent через QueryBus соответствует конвенции project layers.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-14 | backend_developer | Обновление под архитектуру ADR-001 (Integration слой через QueryBus) |
| 2026-02-15 | backend_developer | Реализация: WhisperHealthDto, WhisperHealthCheckComponent, QueryHandler, WhisperHealthCheckerService, Unit тесты, DI конфигурация |
