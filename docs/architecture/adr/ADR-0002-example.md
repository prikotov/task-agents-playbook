# ADR-0002: Internal-First API with Instability Until MMP

**Status:** Accepted
**Date:** 2025-01-19
**Deciders:** Team consensus

---

## Context

TasK разрабатывается как AI-first платформа с публичным API. Однако проект находится на ранних стадиях (MVP → MMP). Возникает дилемма:

- **Стабильность API:** Для внешних пользователей нужен стабильный API с backward compatibility
- **Гибкость развития:** Для MVP нужна возможность быстро менять API без breaking changes для internal users

**Проблема:** Если мы сразу обеспечим backward compatibility, это замедлит разработку и сделает API более rigid. Если не обеспечим stability, это создаст проблемы для ранних adopters.

---

## Decision

Принять **internal-first подход** с **нестабильным публичным API до MMP**.

**Политика:**

1. **MVP phase (Internal use only):**
   - API доступен, но помечен как "unstable/v0"
   - API может меняться без notice и без backward compatibility
   - Internal team использует API для testing и feedback
   - Пользовательская документация внутренняя (internal-only)

2. **MMP phase (Early adopters):**
   - API становится versioned (v1)
   - Объявляется backward compatibility policy (минимум 3 месяца)
   - Breaking changes объявляются заранее через changelog
   - Public documentation опубликована
   - API stability гарантируется

**Реализация:**
- MVP: endpoint URLs include version prefix `/api/v0/...`
- MVP: response headers include warning: `Warning: 299 - "API is unstable and may change without notice"`
- MMP: migrate to `/api/v1/...` with stable contracts

---

## Alternatives

### A1. Stable API from Day 1
**Против:**
- Замедляет разработку (нужно продумывать backward compatibility заранее)
- Over-engineering для MVP
- Никто не использует API на ранних стадиях, зачем оптимизировать?

### A2. No Public API Until MMP
**Против:**
- Теряем возможность собирать feedback от early adopters
- Нет "API-first" подхода в разработке
- Задержка публикации до MMP (потеря времени)

### A3. Separate Internal API vs Public API
**Против:**
- Дублирование endpoints
- Сложность поддержки
- Нет очевидных преимуществ над v0 prefix

### A4. Alpha/Beta/Ga Release Labels
**Против:**
- Проблема: когда переходить с alpha на beta?
- Сложно определить критерии для каждого этапа
- MVP vs MMP уже достаточно чёткое разделение

---

## Consequences

### Positive
- ✅ **Гибкость:** MVP можно быстро развивать без оглядки на backward compatibility
- ✅ **Скорость:** Internal feedback loop работает быстро
- ✅ **API-first:** Разработка ориентирована на API с начала
- ✅ **Простота:** Нет сложного versioning до MMP
- ✅ **Ожидания управляются:** Чёткий signal об нестабильности (v0 prefix + Warning header)

### Negative
- ❌ **Ограниченность adoption:** Внешние пользователи не могут полагаться на API до MMP
- ❌ **Риск:** Early adopters могут игнорировать warnings и использовать v0 API

### Technical Debt
- **Миграционный путь:** Написать migration guide для перехода с v0 на v1
- **Deprecation:** Не нужно (v0 нестабилен по определению)
- **Testing:** Написать integration tests для API contract stability перед MMP

---

## Revisit Criteria

Рассмотреть досрочное стабилизацию API, если:

1. Больше **5 external users** начинают активно использовать v0 API
2. Получаем **сильный demand** на стабильный API от partners
3. MVP реализован раньше чем планировалось и API уже stabilised
4. External team requests integration для pilot проекта

В остальных случаях — следовать плану: стабильный API только на MMP.

---

## Links

- **Related ADR:**
  - [ADR-0001](ADR-0001-example.md) — Vector DB selection (PostgreSQL + pgvector)

- **Related Docs:**
  - [MVP](../../product/mvp.md) — MVP scope (internal use only)
  - [MMP](../../product/mmp.md) — MMP scope (public API with stability)
  - [US-001](../../product/user-stories/US-001-example.md) — Developer searches via API
  - [Vision](../../product/vision.md) — product vision

- **External Resources:**
  - [Semantic Versioning 2.0.0](https://semver.org/)
  - [Microsoft API Design Guide](https://github.com/microsoft/api-guidelines)
