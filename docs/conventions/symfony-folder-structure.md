# Структура папок на Symfony (Symfony Folder Structure)

**Структура папок на Symfony (Symfony Folder Structure)** — организация директорий проекта TasK, основанная на модульной архитектуре DDD и множественных приложениях Symfony.

## Общие правила

- Проект использует модульную архитектуру с четырьмя слоями (Domain, Application, Infrastructure, Integration).
- Каждое приложение находится в `apps/<app_name>/` и наследуется от общего [`Common\Kernel`](src/Kernel.php).
- Все модули размещаются в `src/Module/{ModuleName}/`.
- Конфигурация разделена на общую (`config/`) и приложения (`apps/<app_name>/config/`).
- Тесты разделены по типам: Unit (`tests/Unit/`), Integration (`tests/Integration/`), E2E (`apps/*/tests/`).

## Визуальная схема структуры

```
/
├── apps/                     # Приложения Symfony
│   ├── web/                  # Web-приложение (пользовательский интерфейс)
│   ├── api/                  # API-приложение (RESTful API)
│   ├── console/              # Console-приложение (CLI и worker)
│   └── blog/                 # Blog-приложение (публичный блог)
├── src/                      # Исходный код
│   ├── Kernel.php            # Общий Kernel для всех приложений
│   ├── Exception/            # Общие исключения проекта
│   ├── Module/               # Доменные модули (DDD)
│   └── ValueObject/          # Общие Value Objects
├── config/                   # Общая конфигурация проекта
│   ├── bundles.php           # Регистрация общих bundles
│   ├── modules.php           # Регистрация общих модулей
│   ├── packages/             # Конфигурация пакетов
│   ├── routes/               # Общие маршруты
│   └── services.yaml         # Общая конфигурация сервисов
├── migrations/               # Doctrine миграции
├── tests/                    # Общие тесты
│   ├── Unit/                 # Unit-тесты (Domain, Application)
│   └── Integration/          # Integration-тесты (Infrastructure, Integration)
├── templates/                # Общие шаблоны Twig
├── translations/            # Переводы
├── bin/                      # Исполняемые скрипты
├── public/                   # Публичные файлы (entry points)
├── docs/                     # Документация
└── devops/                   # DevOps скрипты и конфиги
```

## Организация модулей

Модули находятся в `src/Module/{ModuleName}/` и следуют DDD-архитектуре с четырьмя слоями.

```
src/Module/{ModuleName}/
├── Domain/                   # Слой домена (бизнес-логика)
│   ├── Entity/               # Сущности
│   ├── ValueObject/          # Value Objects
│   ├── Enum/                 # Перечисления
│   ├── Specification/        # Спецификации
│   ├── Repository/           # Интерфейсы репозиториев
│   │   └── {RepositoryName}/
│   │       ├── Criteria/     # Критерии поиска
│   │       ├── {RepositoryName}CriteriaInterface.php
│   │       └── {RepositoryName}RepositoryInterface.php
│   └── Service/              # Доменные сервисы
├── Application/              # Слой приложения (use cases)
│   ├── Dto/                  # DTO для передачи данных
│   ├── Enum/                 # Application-перечисления
│   ├── Event/                # События приложения
│   ├── Mapper/               # Мапперы
│   └── UseCase/              # Use Cases
│       ├── Command/          # Команды (CQRS)
│       └── Query/            # Запросы (CQRS)
├── Infrastructure/           # Слой инфраструктуры
│   ├── Component/            # Инфраструктурные компоненты
│   ├── Model/                # Doctrine модели
│   ├── Repository/           # Реализации репозиториев
│   └── Service/              # Инфраструктурные сервисы
├── Integration/              # Слой интеграций
│   ├── Component/            # API-компоненты
│   ├── Listener/             # Слушатели событий
│   └── Service/              # Интеграционные сервисы
├── Resource/                 # Ресурсы модуля
│   └── config/               # Конфигурация модуля
└── {ModuleName}Module.php    # Класс модуля
```

### Правила модулей

- **Domain**: только бизнес-логика, без зависимостей от других слоёв и внешних библиотек.
- **Application**: координация бизнес-логики, без инфраструктурных деталей.
- **Infrastructure**: реализации репозиториев, БД, кэш, файловая система, логирование.
- **Integration**: внешние API, очереди, события, межмодульное взаимодействие.

## Организация приложений

Каждое приложение находится в `apps/<app_name>/` и имеет собственную структуру.

```
apps/<app_name>/
├── config/                   # Конфигурация приложения
│   ├── bundles.php           # Bundles приложения
│   ├── modules.php           # Модули приложения
│   ├── packages/             # Конфигурация пакетов
│   ├── routes/               # Маршруты приложения
│   └── services.yaml         # Конфигурация сервисов
├── src/                      # Исходный код приложения
│   ├── Component/            # Компоненты приложения
│   ├── Controller/           # Контроллеры
│   ├── EventSubscriber/      # Подписчики событий
│   ├── Module/               # Модули приложения
│   └── Security/             # Безопасность
├── templates/                # Шаблоны (для web/blog)
├── tests/                    # Тесты приложения
│   ├── Functional/           # Functional-тесты
│   └── Integration/          # Integration-тесты
└── translations/             # Переводы приложения
```

### Приложения проекта

- **Web** (`apps/web`): основной пользовательский интерфейс с аутентификацией, авторизацией и UI-компонентами.
- **API** (`apps/api`): RESTful API для внешних интеграций и мобильных клиентов.
- **Console** (`apps/console`): CLI-интерфейс для фоновых задач, cron-заданий и административных операций.
- **Blog** (`apps/blog`): публичный блог с контентом и статическими страницами.

## Конфигурационные файлы

Общая конфигурация проекта находится в `config/`:

```
config/
├── bundles.php               # Регистрация общих bundles
├── modules.php               # Регистрация общих модулей
├── packages/                 # Конфигурация пакетов
│   ├── doctrine.yaml         # Doctrine ORM
│   ├── framework.yaml        # Symfony Framework
│   ├── messenger.yaml        # Symfony Messenger
│   └── ...
├── routes/                   # Общие маршруты
│   ├── annotations.yaml      # Аннотации маршрутов
│   └── ...
└── services.yaml             # Общая конфигурация сервисов
```

### Конфигурация приложений

Конфигурация приложения находится в `apps/<app_name>/config/`:

```
apps/<app_name>/config/
├── bundles.php               # Bundles приложения
├── modules.php               # Модули приложения
├── packages/                 # Конфигурация пакетов приложения
├── routes/                   # Маршруты приложения
└── services.yaml             # Конфигурация сервисов приложения
```

## Другие директории

### `migrations/`

Doctrine миграции для схемы базы данных.

```
migrations/
└── VersionYYYYMMDDHHMMSS.php # Миграции (UTC)
```

### `tests/`

Общие тесты проекта.

```
tests/
├── Unit/                     # Unit-тесты (Domain, Application)
│   ├── Module/               # Тесты модулей
│   └── Component/            # Тесты компонентов
└── Integration/              # Integration-тесты (Infrastructure, Integration)
    ├── Module/               # Тесты модулей
    └── Component/            # Тесты компонентов
```

### `templates/`

Общие шаблоны Twig.

```
templates/
└── email/                    # Email-шаблоны
```

### `translations/`

Переводы проекта.

```
translations/
├── messages.en.yaml         # Английские переводы
├── messages.ru.yaml         # Русские переводы
└── ...
```

### `bin/`

Исполняемые скрипты.

```
bin/
├── console                   # Symfony Console
└── phpunit                   # PHPUnit
```

### `public/`

Публичные файлы и entry points.

```
public/
├── index.php                # Entry point для web-приложения
└── assets/                   # Статические ресурсы
```

### `docs/`

Документация проекта.

```
docs/
├── conventions/              # Конвенции проекта
├── git-workflow/             # Git workflow
├── todo/                     # Задачи
└── ...
```

### `devops/`

DevOps скрипты и конфигурации.

```
devops/
├── docker/                   # Docker конфиги
├── supervisor/               # Supervisor конфиги
└── ...
```

## Расположение

```
/                             # Корень проекта
```

## Как используем

- **Создание нового модуля**: создайте директорию `src/Module/{ModuleName}/` с четырьмя слоями (Domain, Application, Infrastructure, Integration).
- **Создание нового приложения**: создайте директорию `apps/<app_name>/` с Kernel, наследуемым от `Common\Kernel`.
- **Добавление модуля в приложение**: зарегистрируйте модуль в `apps/<app_name>/config/modules.php`.
- **Создание миграции**: создайте файл `VersionYYYYMMDDHHMMSS.php` в `migrations/`.
- **Написание тестов**: размещайте unit-тесты в `tests/Unit/`, integration-тесты в `tests/Integration/`.

## Пример

Пример структуры модуля `Chat`:

```
src/Module/Chat/
├── Domain/
│   ├── Entity/
│   │   ├── ChatModel.php
│   │   └── ChatMessageModel.php
│   ├── ValueObject/
│   │   └── MessageVo.php
│   ├── Enum/
│   │   ├── ChatStatusEnum.php
│   │   └── ChatMessageRoleEnum.php
│   ├── Repository/
│   │   ├── Chat/
│   │   │   ├── Criteria/
│   │   │   │   └── ChatFindCriteria.php
│   │   │   ├── ChatCriteriaInterface.php
│   │   │   └── ChatRepositoryInterface.php
│   │   └── ChatMessage/
│   │       ├── Criteria/
│   │       │   └── ChatMessageFindCriteria.php
│   │       ├── ChatMessageCriteriaInterface.php
│   │       └── ChatMessageRepositoryInterface.php
│   └── Service/
│       └── LlmManager/
│           └── LlmManagerServiceInterface.php
├── Application/
│   ├── UseCase/
│   │   ├── Command/
│   │   │   └── Chat/
│   │   │       └── Create/
│   │   │           ├── CreateCommand.php
│   │   │           └── CreateCommandHandler.php
│   │   └── Query/
│   │       └── Chat/
│   │           └── Find/
│   │               ├── FindQuery.php
│   │               └── FindQueryHandler.php
│   └── Dto/
│       └── ChatDto.php
├── Infrastructure/
│   ├── Model/
│   │   ├── ChatModel.php
│   │   └── ChatMessageModel.php
│   ├── Repository/
│   │   ├── Chat/
│   │   │   └── ChatRepository.php
│   │   └── ChatMessage/
│   │       └── ChatMessageRepository.php
│   └── Service/
│       └── ChatRender/
│           └── ChatRendererAsHtml.php
├── Integration/
│   ├── Listener/
│   │   └── ProjectOptions/
│   │       └── UpdatedEventListener.php
│   └── Service/
│       └── LlmManager/
│           └── LlmManagerService.php
├── Resource/
│   └── config/
│       └── services.yaml
└── ChatModule.php
```

Пример конфигурации модулей для web-приложения (`apps/web/config/modules.php`):

```php
<?php

declare(strict_types=1);

return [
    Web\Module\AppOption\AppOptionModule::class => ['all' => true],
    Web\Module\Chat\ChatModule::class => ['all' => true],
    Web\Module\Project\ProjectModule::class => ['all' => true],
    Web\Module\User\UserModule::class => ['all' => true],
];
```

## Чек-лист для проведения ревью кода

- [ ] Модуль находится в `src/Module/{ModuleName}/`.
- [ ] Модуль содержит четыре слоя: Domain, Application, Infrastructure, Integration.
- [ ] Приложение находится в `apps/<app_name>/`.
- [ ] Kernel приложения наследуется от `Common\Kernel`.
- [ ] Модули зарегистрированы в `apps/<app_name>/config/modules.php`.
- [ ] Конфигурация разделена на общую (`config/`) и приложения (`apps/<app_name>/config/`).
- [ ] Unit-тесты находятся в `tests/Unit/`.
- [ ] Integration-тесты находятся в `tests/Integration/`.
- [ ] Миграции находятся в `migrations/` и следуют формату `VersionYYYYMMDDHHMMSS.php`.
- [ ] Документация соответствует правилам из `docs/conventions/doc-writing-rules.md`.
