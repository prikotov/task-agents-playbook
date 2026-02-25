# Слой Представления (Presentation Layer)

**Слой Представления (Presentation Layer)** — внешний интерфейс приложения, отвечающий за приём запросов, валидацию входных данных, вызов Application-слоя и формирование ответа. Является точкой входа для всех внешних взаимодействий.

## Общие правила

- Контроллеры, консольные команды и HTTP-эндпоинты обращаются **только** к UseCase/Handler из Application слоя.
- Публичные контракты Presentation формируют входные данные в Request DTO/Command/Query и принимают Response DTO.
- **Запрещено** использовать типы из Domain напрямую (Entity, VO, Repository, Specification).
- **Запрещено** обращаться к классам из Infrastructure/Integration слоёв.
- Исключения маппятся в ответы/сообщения через обработчики уровня Presentation (listeners/subscribers/exception mappers).
- Валидация ввода — на уровне формы/DTO или делегируется в Application.
- Web-контроллеры возвращают HTML (Twig), API-контроллеры — JSON.
- Console-команды используют `SymfonyStyle` для вывода и корректные коды завершения.

## Зависимости

### Разрешённые зависимости

| Слой | Что можно использовать |
|------|------------------------|
| Application | Command, Query, DTO, Handler (через `__invoke`) |
| Common | Исключения, хелперы, общие компоненты |
| Symfony | HttpFoundation, Form, Validator, Security, Console |
| Twig | Шаблоны, функции, фильтры |

### Запрещённые зависимости

| Слой | Что запрещено |
|------|---------------|
| Domain | Entity, ValueObject, Repository, Specification, Service |
| Infrastructure | Repository implementation, Model, Cache, External API |
| Integration | Listener, External Service, Event |

Подробнее о взаимодействии слоёв: [layers.md](../layers.md)

## Расположение

Presentation слой реализован через четыре приложения:

```
apps/
├── web/                    # Веб-интерфейс
│   └── src/
│       ├── Module/
│       │   └── {ModuleName}/
│       │       ├── Controller/
│       │       ├── Form/
│       │       ├── Security/
│       │       └── Resource/templates/
│       └── Component/
├── api/                    # REST API
│   └── src/
│       ├── Controller/
│       ├── Dto/
│       └── Security/
├── console/                # CLI-команды
│   └── src/
│       └── Command/
└── blog/                   # Публичный блог
    └── src/
        └── Controller/
```

## Как используем

### Web-приложение

**Назначение:** Веб-интерфейс для авторизованных пользователей.

**Технологии:**
- Symfony UX (Turbo, Stimulus) для интерактивности
- Phoenix-компоненты Bootstrap 5 для UI
- Сессии и cookies для аутентификации
- Формы (FormType) для ввода данных
- Flash-сообщения для обратной связи

**Поток запроса:**
1. Контроллер получает HTTP-запрос
2. Создаёт Form из Request или разбирает параметры
3. При успехе — создаёт Command/Query и вызывает Handler
4. Возвращает Response (RedirectResponse или Twig-шаблон)

### API-приложение

**Назначение:** REST API для внешних клиентов и мобильных приложений.

**Технологии:**
- JWT-токены для аутентификации
- Stateless-архитектура
- Версионирование через URL-префикс (`/api/v1/`)
- OpenAPI/Swagger документация

**Поток запроса:**
1. Контроллер получает HTTP-запрос с JSON body
2. Десериализует в Request DTO
3. Создаёт Command/Query и вызывает Handler
4. Возвращает JSON Response с соответствующим статусом

### Console-приложение

**Назначение:** CLI-команды для cron-задач и административных операций.

**Технологии:**
- `#[AsCommand]` атрибут для регистрации
- `LockFactory` для защиты от параллельного запуска
- `SymfonyStyle` для прогресс-баров и таблиц
- Коды завершения (`SUCCESS`/`FAILURE`)

**Поток выполнения:**
1. Команда получает аргументы и опции
2. Валидирует входные данные
3. Вызывает Handler через Command/Query
4. Выводит результат через `SymfonyStyle`

### Blog-приложение

**Назначение:** Публичный блог и статические страницы.

**Технологии:**
- Публичный доступ без аутентификации
- SEO-оптимизация
- Кэширование страниц

## Структура приложений

| Приложение | URL-префикс | Аутентификация | Формат ответа |
|------------|-------------|----------------|---------------|
| Web | `/` | Session/Cookie | HTML (Twig) |
| API | `/api/` | JWT | JSON |
| Blog | `/blog/` | Нет | HTML (Twig) |
| Console | CLI | Нет | Text |

## Дочерние документы

- [Контроллер (Controller)](controller.md)
- [Контроллер списка (List Controller)](list-controller.md)
- [Консольная команда (Console Command)](console_command.md)
- [Формы (Forms)](forms.md)
- [Маршруты (Route)](route.md)
- [Представление (View)](view.md)
- [Авторизация (Authorization)](authorization.md)
- [Перечисление прав (Permission Enum)](permission_enum.md)
- [Перечисление действий (Action Enum)](action_enum.md)
- [Правило (Rule)](rule.md)
- [Голосователь (Voter)](voter.md)
- [Грант (Grant)](grant.md)
