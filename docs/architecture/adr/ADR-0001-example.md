# ADR-0001: Use PostgreSQL + pgvector as Primary Vector Storage

**Status:** Accepted
**Date:** 2025-01-19
**Deciders:** Team consensus

---

## Context

TasK требует хранилища векторов (embeddings) для реализации RAG-поиска. Векторное хранилище должно:

1. Хранить embeddings (1536+ dimensions) для каждого текстового chunk
2. Поддерживать быстрый approximate nearest neighbour (ANN) search
3. Интегрироваться с существующей инфраструктурой (PostgreSQL уже используется)
4. Быть достаточно производительным для MVP масштаба (десятки-сотни тысяч vectors)

**Проблема выбора:** Существует несколько вариантов: специализированные vector DB (Qdrant, Milvus, Weaviate) и расширения для реляционных БД (PostgreSQL + pgvector, MySQL + vector).

---

## Decision

Для MVP используем **PostgreSQL + pgvector** как основное хранилище векторов.

**Детали реализации:**
- Установка pgvector расширения в PostgreSQL
- Таблица `chunks` с колонкой `embedding` (vector(1536))
- Использование HNSW индекса для ANN search
- SQL запросы через Doctrine ORM

**Миграция на MMP:** После MVP можно рассмотреть Qdrant, если производительности PostgreSQL будет недостаточно.

---

## Alternatives

### A1. Qdrant (отдельный сервис)
**Против:**
- Добавляет новый сервис в инфраструктуру (плюс运维 нагрузка)
- Требует отдельной настройки деплоя и мониторинга
- Нет очевидных преимуществ для MVP масштаба
- Усложняет локальное развитие

### A2. Milvus
**Против:**
- Требует Kubernetes для продакшн (overkill для MVP)
- Слишком сложный setup для MVP
- Фокус на Enterprise масштабе

### A3. Weaviate
**Против:**
- Требует отдельный сервис (плюс运维)
- Меньше опыта у команды

### A4. MySQL + vector extension
**Против:**
- Проект уже использует PostgreSQL
- pgvector более зрелый и популярный

---

## Consequences

### Positive
- ✅ **Простота:** Нет новых сервисов — использующий существующую PostgreSQL
- ✅ **Скорость разработки:** Нет необходимости настраивать инфраструктуру для vector DB
- ✅ **Data consistency:** Все данные (метаданные + vectors) в одной транзакционной БД
- ✅ **Backup:** PostgreSQL backup покрывает и vectors
- ✅ **Local dev:** Docker compose уже включает PostgreSQL
- ✅ **Performance достаточна для MVP:** HNSW индекс обеспечивает ANN search

### Negative
- ❌ **Масштабируемость:** PostgreSQL может стать bottleneck для миллионов vectors (но это post-MMP)
- ❌ **Специализированные фичи:** Нет продвинутых фич векторных БД (фильтрация по метаданным, custom distance metrics)

### Technical Debt
- **Performance monitoring:** Нужно следить за производительностью PostgreSQL (индексы, query plans)
- **Миграционный путь:** В будущем может потребоваться миграция на Qdrant или другую vector DB (если масштаб вырастет)
- **HNSW tuning:** Параметры индекса (M, ef_construction) могут потребовать настройки

---

## Revisit Criteria

Рассмотреть альтернативу (Qdrant, Milvus), если:

1. В production более **1 million vectors** и latency search > 2s (p95)
2. PostgreSQL CPU/memory usage > 80% из-за vector search
3. Появляется необходимость в advanced vector DB features (метаданные-фильтрация, custom distance)
4. Team получает опыт с Qdrant в других проектах

---

## Links

- **Related ADR:**
  - [ADR-0002](ADR-0002-example.md) — Internal-first API policy

- **Related Docs:**
  - [MVP](../../product/mvp.md) — MVP scope and performance requirements
  - [Vision](../../product/vision.md) — product vision
  - [US-001](../../product/user-stories/US-001-example.md) — Developer uploads PDF and searches

- **External Resources:**
  - [pgvector GitHub](https://github.com/pgvector/pgvector)
  - [pgvector documentation](https://github.com/pgvector/pgvector#readme)
  - [Qdrant documentation](https://qdrant.tech/documentation/)
