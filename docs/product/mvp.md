# Minimum Viable Product (MVP)

## Определение

MVP (Minimum Viable Product) для TasK — это **internal tool** для внутренней команды разработчиков. Цель MVP — проверить основные гипотезы обработки данных и RAG-поиска в реальных условиях, подготовить базовую инфраструктуру для дальнейшего развития продукта.

MVP **не предназначен** для внешних пользователей и не должен быть публично доступен.

---

## Входит в состав MVP

### Основные функции
| Feature | Описание |
|---------|-------------|
| **Source Ingestion** | Загрузка файлов из локальной файловой системы и HTTP-URL |
| **Text Extraction** | Извлечение текста из PDF (basic), DOCX, TXT |
| **Audio Transcription** | Базовая транскрибация аудио-файлов |
| **Audio Diarization** | Идентификация спикеров в аудио/видео — извлечение экспертных мнений с привязкой к автору |
| **Chunking** | Разбиение текста на chunks для vector search |
| **Embeddings** | Генерация embeddings через Ollama или внешний LLM |
| **Vector Storage** | Хранение векторов в PostgreSQL + pgvector |
| **Semantic Search** | Базовый semantic search по индексу |
| **CLI Interface** | Console команды для ingestion и query |
| **Basic Web UI** | Простой веб-интерфейс для просмотра источников и поиска |

### Инфраструктура
- Docker/Podman compose для локального запуска
- PostgreSQL + pgvector для vector storage
- RabbitMQ для очередей (optional для MVP, можно defer)
- Minio для S3-совместимого хранения (basic usage)
- Worker CLI для фоновой обработки

### API (Internal)
- Basic REST API для ingestion и search
- API documentation (internal use only)
- **Non-production-ready**: API может изменяться без notice

---

## Не входит в состав MVP

| Feature | Почему не входит |
|---------|------------------|
| User authentication/authorization | Только внутренняя команда, нет внешних пользователей |
| Multi-tenancy / project isolation | Single project для MVP |
| Onboarding for external users | MVP только для внутреннего использования |
| Production-ready security | Базовая безопасность только для внутреннего использования |
| Full API documentation | Внутренняя документация достаточна |
| Email notifications | Не требуется для MVP |
| Public deployment guides | Только dev-окружение |
| Performance optimization | Правильность важнее производительности |
| Error handling edge cases | Базовая обработка ошибок достаточна |
| Monitoring/observability | Только базовое логирование |
| Backup/restore procedures | Только development данные |

---

## Критерии приёмки

MVP считается готовым, когда:

1. [ ] Внутренняя команда может загружать PDF/DOCX/TXT через CLI или basic UI
2. [ ] Система автоматически извлекает текст и транскрибирует аудио
3. [ ] Система выполняет диаризацию аудио/видео с идентификацией спикеров
4. [ ] Пользователь может выполнять semantic search по индексу через API
5. [ ] Basic web-UI позволяет просматривать sources и результаты поиска
6. [ ] Pipeline обрабатывает **минимум 50 документов** без критических ошибок
7. [ ] Search latency для **простых запросов** < 2 секунд
8. [ ] CLI команды работают корректно (ingestion, query, status)
9. [ ] Docker compose поднимает full stack одной командой
10. [ ] Basic error logging работает (настроен monolog)

---

## Связанные User Stories

- **[US-001](user-stories/US-001-example.md)** — Разработчик загружает PDF через CLI и ищет через API
- **[US-002](user-stories/US-002-example.md)** — Product manager создаёт проект и команда ищет через веб

---

## Риски

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| **API instability** | High | Medium | Документировать как internal-only, добавить version prefix (/v1-alpha/) |
| **Extraction quality** | Medium | High | Использовать несколько инструментов (pdfinfo, Docling и т.д.), ручной просмотр |
| **Performance issues** | Medium | Medium | MVP не фокусируется на производительности, отложить оптимизацию |
| **Vector DB limitations** | Low | Medium | PostgreSQL + pgvector достаточно для масштаба MVP |
| **Worker crashes** | Medium | High | Реализовать логику повтора, мониторить длину очереди |

---

## Планирование после MVP

После MVP:

1. **Stabilize API** — исправить несоответствия, добавить versioning
2. **Add authentication** — basic auth для early adopters
3. **Improve UI** — добавить управление проектами, улучшить UX
4. **Security hardening** — production-ready настройка
5. **Monitoring** — добавить метрики, логирование, оповещения
6. **Performance** — оптимизировать индексацию и поиск

См. [MMP](mmp.md) для следующего этапа.
