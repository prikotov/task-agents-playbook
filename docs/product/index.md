# Product Documentation

## Что такое продуктовый контур

Продуктовый контур — это набор документов, описывающих видение продукта, стратегию разработки, пользовательские истории и критерии готовности.

## Структура контура

### PRD (Product Requirements Document)

- **[prd.md](prd.md)** — единый документ требований, агрегирующий все продуктовые артефакты

### Основные документы

- **[vision.md](vision.md)** — видение продукта, целевая аудитория, ценность и принципы
- **[mission.md](mission.md)** — миссия и ценностный фундамент продукта
- **[strategy.md](strategy.md)** — бизнес-цели и стратегии их достижения
- **[mvp.md](mvp.md)** — Minimum Viable Product — внутренний инструмент для команды
- **[mmp.md](mmp.md)** — Minimum Marketable Product — минимальная версия для выхода на рынок
- **[story-mapping.md](story-mapping.md)** — user story map с backbone activities и release slices

## User Stories

Детальные пользовательские истории в формате, удобном для разработки:

- **[user-stories/](user-stories/)** — директория с пользовательскими историями
  - [US-001-example.md](user-stories/US-001-example.md) — разработчик загружает PDF через CLI и ищет через API
  - [US-002-example.md](user-stories/US-002-example.md) — продукт-менеджер создаёт проект и команда ищет через веб

## Использование

1. **Для планирования** — используйте vision.md и story-mapping.md для понимания направления
2. **Для разработки** — обращайтесь к конкретным user stories в `user-stories/`
3. **For оценки готовности** — используйте критерии из mvp.md и mmp.md

## Ссылки

- [Architecture Decision Records](../architecture/adr/) — ADR-журнал
- [Development setup](../devops/development/) — настройка dev-окружения
- [Git workflow](../git-workflow/) — работа с git и PR
