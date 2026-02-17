# Architecture Decision Records (ADR)

## Что такое ADR

ADR (Architecture Decision Record) — это документ, описывающий важное архитектурное решение, принятое в проекте. ADR фиксирует контекст решения, альтернативы, последствия и критерии пересмотра.

ADR — это **не спецификация**, а историческая запись принятых решений. Она помогает:

- Новым участникам команды понять, почему сделан тот или иной выбор
- Избежать повторного обсуждения уже решённых вопросов
- Легко найти и пересмотреть устаревшие решения

---

## Когда писать ADR

Пишите ADR для решений, которые:

1. **Влияют на архитектуру системы:** выбор технологии, паттерна, подхода
2. **Имеют значительные последствия:** трудоёмкое изменение, долгосрочное влияние
3. **Затрагивают несколько компонентов:** изменения, затрагивающие модули, слои
4. **Были приняты после обсуждения альтернатив:** если рассматривались разные варианты

**НЕ пишите ADR для:**
- Тривиальных решений (выбор библиотеки для форматирования даты)
- Временных решений без долгосрочного влияния
- Bug fixes и оптимизации без архитектурных изменений

---

## Формат ADR

```markdown
# ADR-XXXX: [Title]

**Status:** [Proposed|Accepted|Deprecated|Superseded]
**Date:** YYYY-MM-DD
**Deciders:** [@username, @username]

## Context

[What is the situation? What problem are we trying to solve?]

## Decision

[What did we decide? Keep it concise.]

## Alternatives

[What alternatives did we consider? Why did we reject them?]

## Consequences

### Positive
- [What are the benefits?]

### Negative
- [What are the downsides?]

### Technical Debt
- [What debt do we incur?]

## Revisit Criteria

[When should we reconsider this decision?]

## Links

- [ADR-XXXX](ADR-XXXX.md) — Related ADR
- [link to docs] — Related documentation
```

---

## Нумерация

- ADR нумеруются последовательными номерами: `ADR-0001`, `ADR-0002`, `ADR-0003`, ...
- Номера **не пропускаются** (если решение отменено, его статус меняется, а номер остаётся)
- Используйте 4 цифры для удобства (ADR-0001, ADR-0002, ...)

---

## Статусы

| Статус | Описание |
|--------|----------|
| **Proposed** | Предложено, но ещё не принято |
| **Accepted** | Принято и реализовано |
| **Deprecated** | Устарело, но ещё не заменено (should not be used for new work) |
| **Superseded** | Заменено другим ADR (указывается номер нового ADR) |

**Примеры:**
- `**Status:** Superseded by ADR-0005` — решение заменено ADR-0005
- `**Status:** Deprecated` — решение устарело, использовать не следует

---

## Как заменять ADR

Когда решение нужно изменить:

1. **Не редактируйте** существующий ADR — это историческая запись
2. Создайте **новый ADR** с новым номером
3. В старом ADR измените статус на `Superseded by ADR-XXXX`
4. В новом ADR укажите ссылку на старый ADR

**Пример:**

```markdown
# ADR-0002: Use Qdrant for Vector DB

**Status:** Superseded by ADR-0007
**Date:** 2025-01-15

...

## Links

- [ADR-0007](ADR-0007.md) — Supersedes this ADR
```

```markdown
# ADR-0007: Use PostgreSQL + pgvector for Vector DB

**Status:** Accepted
**Date:** 2025-03-10

...

## Links

- [ADR-0002](ADR-0002.md) — Superseded by this ADR
```

---

## Где хранить

ADR хранятся в этой директории: `docs/architecture/adr/`

Каждый ADR — отдельный файл в формате markdown: `ADR-XXXX-title.md`

**Примеры:**
- [ADR-0001-example.md](ADR-0001-example.md) — Выбор первичного хранилища векторов
- [ADR-0002-example.md](ADR-0002-example.md) — Internal-first и нестабильность API до MMP

---

## Связь с документацией

ADR должны ссылаться на связанную документацию:

- **Product docs:** [Vision](../../product/vision.md), [MVP](../../product/mvp.md), [MMP](../../product/mmp.md)
- **Architecture docs:** [Overview](../overview.md), [Module design](../module/)
- **User Stories:** [US-XXX](../../product/user-stories/)

---

## Related Resources

- [Microsoft's Architecture Decision Records](https://learn.microsoft.com/en-us/azure/architecture/patterns/decision-records)
- [ADR by Michael Nygard](https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions)
