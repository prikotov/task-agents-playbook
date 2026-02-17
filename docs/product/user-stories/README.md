# User Stories

## Что такое User Story?

User Story — это краткое описание функционала с точки зрения пользователя. Формат:

> **Как** [роль],
> **Я хочу** [действие],
> **Чтобы** [ценность].

## Format (Формат)

Каждая user story в этом каталоге следует единому формату:

```markdown
# US-XXX: [Title]

## Actor/Context
[Who is the user and in what context?]

## Goal (Цель)
[What does the user want to achieve?]

## Value (Ценность)
[Why is this valuable?]

## Story
Как [role], я хочу [action], чтобы [value].

## Acceptance Criteria (Критерии приёмки)
- [ ] AC1
- [ ] AC2
- ...

## Non-Goals (Не цели)
- What is NOT included in this story

## Notes/Open Questions (Заметки/Открытые вопросы)
- Any notes or questions to resolve

## Links (Связи)
- Related stories: US-XXX, US-XXX
- Related docs: [link]
```

## Создание новых User Stories

1. **Naming (Именование)**: `US-XXX-[short-title].md`, где XXX — трёхзначный номер (001, 002, ...)
2. **Title (Заголовок)**: Краткое, описательное название на русском
3. **Actor (Актёр)**: Определите, кто пользователь (Developer, Product Manager, etc.)
4. **Context (Контекст)**: Опишите контекст использования
5. **Goal (Цель)**: Чётко сформулируйте цель
6. **Value (Ценность)**: Объясните, почему это важно
7. **Acceptance Criteria (Критерии приёмки)**: Конкретные, проверяемые критерии
8. **Non-Goals (Не цели)**: Чётко укажите, что НЕ включается
9. **Links (Связи)**: Свяжите с другими US и документацией

## Примеры

- **[US-001-example.md](US-001-example.md)** — Developer uploads PDF via CLI and searches through API
- **[US-002-example.md](US-002-example.md)** — Product manager creates project and team searches via web

## Отслеживание статусов

Используйте метки в начале файла:

- ✅ Done (Готово)
- ⏳ In Progress (В работе)
- 📋 Planned (Запланировано)

## Связанные документы

- [Vision](../vision.md) — product vision
- [MVP](../mvp.md) — MVP scope and acceptance criteria
- [MMP](../mmp.md) — MMP scope and release criteria
- [Story Mapping](../story-mapping.md) — release planning
