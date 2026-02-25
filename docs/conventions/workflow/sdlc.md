# Software Development Life Cycle (SDLC) & Kanban

Процесс разработки TasK основан на принципах **Kanban**: поток задач визуализирован, сигналы минимизированы, а движение управляется состоянием доски в `todo/`.

## Workflow (Sequence Diagram)

```mermaid
sequenceDiagram
    autonumber
    participant PO as Product Owner
    participant SA as System Analyst
    participant UX as UI/UX Designer
    participant Arch as Architect
    participant Lead as Team Lead
    participant Dev as Developer
    participant Rev as Reviewer
    participant QA as QA Engineer
    participant Ops as DevOps
    participant TW as Tech Writer
    participant Copy as Copywriter

    Note over PO, Copy: Канбан-доска (todo/ & docs/product/)

    PO->>PO: Backlog: Новая Story / Идея
    
    SA->>PO: Pull Story: Анализ и декомпозиция
    SA->>UX: Request: UI/UX дизайн
    UX-->>SA: Макеты / Прототипы
    SA->>Arch: Request: Архитектурный фильтр
    Arch-->>SA: ADR / Constraints
    SA->>QA: Push: Сценарии тестирования (Gherkin)
    SA->>Lead: Статус: Technical Draft Ready
    
    Lead->>Lead: Валидация процесса и тех. требований
    Lead->>SA: Статус: Ready for Execution

    alt Тип задачи: Новая фича / Баг
        Dev->>Lead: Pull: Разработка (In Progress)
        Dev->>Dev: Код + Unit тесты
        Rev->>Dev: Pull PR: Code Review
        Rev->>Rev: Статус: Ready for Test
    else Тип задачи: Инфраструктура / CI
        Ops->>Lead: Pull: Настройка (In Progress)
        Ops->>Ops: Конфиги / Скрипты
        Rev->>Ops: Pull PR: Infra Review
        Rev->>Rev: Статус: Ready for Test
    end
    
    QA->>Rev: Pull: Верификация (Testing)
    Note over QA: Web E2E (Panther) / API E2E
    QA-->>Dev: Если баг: Bug Report
    QA->>Lead: Статус: Ready for Release
    
    Lead->>Lead: E2E тесты пройдены (make tests-e2e)
    Lead->>PO: Signal: Technical Acceptance (Ready)
    PO->>PO: Решение о релизе (Business Value)
    
    PO->>Ops: Команда: Go Live!
    Ops->>Ops: Deploy to Production
    
    par Документирование
        TW->>Ops: Pull: Технические детали релиза
        PO->>TW: Context: Ценность для пользователя
        TW->>TW: Update docs/user/ & CHANGELOG.md
    and Промо
        Copy->>TW: Pull: Список изменений (Release Notes)
        PO->>Copy: Context: Тональность маркетинга
        Copy->>Copy: Write Blog Post / SMM
    end
    
    TW->>PO: Done: Документация готова
    Copy->>PO: Done: Промо готово
```

## Колонки Канбан-доски

Процесс визуализирован через статусы задач в `todo/`:

1.  **Backlog:** Идеи от **Product Owner**.
2.  **Analysis:** Работа **System Analyst**, **UI/UX Designer** и **Architect**.
3.  **Ready for Dev/Infra:** Проверено **Team Lead**. Очередь исполнения.
4.  **In Progress:** Активная работа (**Dev** или **DevOps**).
5.  **Review:** Аудит от **Reviewer**.
6.  **Testing:** Верификация от **QA**.
7.  **Ready for Release:** Ждет решения **Product Owner**.
8.  **Deployment:** Выкатка в Prod от **DevOps**.
9.  **Done:** Работа **Tech Writer** и **Copywriter** завершена.

## Принципы потока

*   **WIP Limits:** Агенты не берут новую задачу, пока не завершили текущую.
*   **Pull System:** Исполнители сами берут задачи из очереди "Ready", согласованной Лидом.
*   **Knowledge Enrichment:** Задача обогащается на каждом этапе (ТЗ -> Код -> Тесты -> Доки).
*   **Team Lead Role:** Выступает "вратарём" (Technical Gatekeeper), проверяя качество постановки перед разработкой и подтверждая тех. готовность перед бизнесом.
