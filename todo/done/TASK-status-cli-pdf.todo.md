# TASK-status-cli-pdf: PDF health checker (Integration Layer)

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
- **Ветка**: task/TASK-status-cli-pdf
- **PR**: #2113
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как оператор системы,
я хочу видеть работоспособность PDF обработчика,
чтобы понимать доступность функции извлечения текста из PDF.

### Цель (SMART)
Реализовать health check для PDF обработчика через Integration слой:
1) Query UseCase в Source Module для проверки здоровья;
2) Integration Service в Health Module (вызывает Query через QueryBus);
3) Переиспользование существующего PdfinfoComponent (метод getVersion()).

## 2. Контекст и Границы (Scope)
**Где делаем:**
- Source Module: `src/Module/Source/Application/UseCase/Query/CheckPdfHealth/`
- Health Module: `src/Module/Health/Integration/Service/HealthChecker/`

**Архитектурный контекст (ADR-001):**
- Integration слой вызывает Source Module через QueryBus
- Переиспользуется существующий PdfinfoComponent (метод getVersion() добавлен)
- Результат сохраняется в БД через cron на Worker Server

**Границы (Out of Scope):**
- Реальное извлечение текста из PDF
- Проверка всех PDF библиотек
- Метрики качества извлечения
- Console команда (отдельная задача TASK-status-cli-console)

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] DTO `PdfHealthDto` с полями: isHealthy, version, errorMessage
- [x] Query `CheckPdfHealthQuery` в Source Module
- [x] QueryHandler `CheckPdfHealthQueryHandler` использующий PdfinfoComponentInterface
- [x] Метод `getVersion()` добавлен в существующий `PdfinfoComponent`
- [x] Service `PdfHealthCheckerService` в Health/Integration/Service через QueryBus
- [x] Проверка: pdfinfo binary exists (poppler-utils)
- [x] Unit тесты для QueryHandler

### 🟡 Should Have
- [x] Возврат версии poppler

### 🟢 Could Have
- [ ] Проверка OCR поддержки (tesseract)

### ⚫ Won't Have
- [ ] Реальное извлечение текста
- [ ] Прямой вызов PDF компонента из Health Module (нарушение конвенции)
- [ ] Создание отдельного PdfHealthCheckComponent (переиспользуем PdfinfoComponent)

## 4. План реализации (Tasks)

### Phase 1: Source Module Query UseCase
1. [x] Создать `PdfHealthDto.php` в Source/Application/UseCase/Query/CheckPdfHealth (co-location)
2. [x] Создать `CheckPdfHealthQuery.php` в Source/Application/UseCase/Query/CheckPdfHealth
3. [x] Создать `CheckPdfHealthQueryHandler.php` использующий PdfinfoComponentInterface
4. [x] Добавить метод `getVersion()` в `PdfinfoComponentInterface` и `PdfinfoComponent`
5. [x] Написать Unit тест для QueryHandler

### Phase 2: Health Module Integration Service
6. [x] Создать `PdfHealthCheckerService.php` в Health/Integration/Service/HealthChecker
7. [x] Обновить DI конфигурацию
8. [x] Обновить интеграционные тесты (добавить getVersion в моки)

## 5. Структура файлов

```
src/Module/Source/
├── Application/
│   └── UseCase/
│       └── Query/
│           └── CheckPdfHealth/
│               ├── CheckPdfHealthQuery.php
│               ├── CheckPdfHealthQueryHandler.php
│               └── PdfHealthDto.php              # co-located with UseCase
└── Infrastructure/
    └── Component/
        └── Pdfinfo/
            ├── PdfinfoComponentInterface.php    # добавлен метод getVersion()
            └── PdfinfoComponent.php              # добавлена реализация getVersion()

src/Module/Health/
└── Integration/
    └── Service/
        └── HealthChecker/
            └── PdfHealthCheckerService.php
```

## 6. Критерии приемки (Definition of Ready)
- [x] QueryHandler корректно использует PdfinfoComponentInterface
- [x] Integration Service вызывает Query через QueryBus
- [x] Результат маппится в HealthCheckResultVo
- [x] Unit тесты проходят
- [x] DI конфигурация корректна

## 7. Самопроверка (Verification)
```bash
make tests-unit
make tests-integration
make check
```

## 8. Риски и Зависимости
- poppler-utils должен быть установлен в системе
- QueryBus должен быть настроен для cross-module communication

## 9. Источники
- [x] [ADR-001 в EPIC-status-page](EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [x] [Poppler](https://poppler.freedesktop.org/)
- [x] [Конвенция Application Layer](../docs/conventions/layers/application.md)

## 10. Комментарии
Метод `getVersion()` добавлен в существующий `PdfinfoComponent` вместо создания отдельного `PdfHealthCheckComponent`. Это соответствует принципу расширения существующих компонентов.

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-12 | system_analyst | Создание задачи |
| 2026-02-14 | backend_developer | Обновление под архитектуру ADR-001 (Integration слой через QueryBus) |
| 2026-02-15 | backend_developer | Реализация: PdfHealthDto, Query/Handler, PdfHealthCheckComponent, PdfHealthCheckerService, Unit тесты (PR #2113) |
| 2026-02-15 | backend_developer | Рефакторинг: метод getVersion() добавлен в PdfinfoComponent вместо отдельного PdfHealthCheckComponent |
