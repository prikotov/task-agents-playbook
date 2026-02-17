# Приложения на фреймворке Symfony (Symfony Applications)

**Приложение Symfony (Symfony Application)** — изолированный экземпляр Symfony Kernel с собственным набором модулей, конфигурацией и назначением. Подробности: [Symfony Applications](https://symfony.com/doc/current/configuration/multiple_applications.html).

## Общие правила

- Каждое приложение находится в директории `apps/<app_name>/`.
- Все приложения наследуются от общего [`Common\Kernel`](src/Kernel.php).
- Каждое приложение имеет собственный идентификатор (`id`), который используется для разделения кэша и логов.
- Конфигурация приложения находится в `apps/<app_name>/config/`.
- Модули приложения регистрируются в `apps/<app_name>/config/modules.php`.
- Приложения могут переопределять или дополнять общую конфигурацию из `config/`.
- Каждое приложение имеет собственные тесты в `apps/<app_name>/tests/`.

## Структура приложения

```
apps/<app_name>/
├── config/
│   ├── bundles.php          # Регистрация bundles приложения
│   ├── modules.php          # Регистрация модулей приложения
│   ├── packages/            # Конфигурация пакетов
│   ├── routes/              # Маршруты приложения
│   └── services.yaml        # Конфигурация сервисов
├── src/                     # Исходный код приложения
│   ├── Component/           # Компоненты приложения
│   ├── Controller/          # Контроллеры
│   ├── EventSubscriber/     # Подписчики событий
│   ├── Module/              # Модули приложения
│   └── Security/           # Безопасность
├── templates/               # Шаблоны (для web/blog)
├── tests/                   # Тесты приложения
└── translations/            # Переводы приложения
```

## Расположение

```
apps/<app_name>/
```

## Назначение приложений

### Web (`apps/web`)

**Web-приложение** — основной пользовательский интерфейс проекта с поддержкой аутентификации, авторизации и UI-компонентов.

- **Назначение**: веб-интерфейс для пользователей, включая dashboard, управление проектами, чатами и т.д.
- **Особенности**:
  - Полная поддержка Symfony Security (аутентификация через OAuth2, email, ESIA).
  - Twig-шаблоны и Twig Components.
  - AssetMapper и Stimulus-контроллеры.
  - Mercure для real-time обновлений.
  - Модули с UI-компонентами (Phoenix, формы, виджеты).
- **Модули**: AppOption, Attribution, Billing, Chat, Dashboard, Landing, Llm, Project, Rag, Search, Source, Secret, User, Tag, Notification.
- **Типы тестов**: Functional (страницы), Integration (подписчики событий), Unit (компоненты).

### API (`apps/api`)

**API-приложение** — RESTful API для интеграции с внешними системами и мобильными клиентами.

- **Назначение**: программный интерфейс для работы с данными проекта.
- **Особенности**:
  - RESTful-эндпоинты.
  - JSON-формат запросов и ответов.
  - CORS-поддержка через NelmioCorsBundle.
  - OpenAPI-документация через NelmioApiDocBundle.
  - Версионирование API через пространство имён (`Api\v1\`).
- **Модули**: Chat, Project (API v1).
- **Типы тестов**: Integration (API-эндпоинты).

### Console (`apps/console`)

**Console-приложение** — CLI-интерфейс для выполнения фоновых задач, cron-заданий и административных операций.

- **Назначение**: консольные команды, обработка очередей, миграции, технические скрипты.
- **Особенности**:
  - Symfony Console Commands.
  - Интеграция с Symfony Messenger для обработки очередей.
  - Доступ к утилитам (pdfinfo, MinerU/Docling и др.) в контейнере `worker-cli`.
  - Отсутствие веб-интерфейса и HTTP-маршрутов.
- **Модули**: Chat, Llm, Project, Rag, Source, SpeechToText, User, Billing, Notification, Fix.
- **Типы тестов**: Integration (консольные команды).

### Blog (`apps/blog`)

**Blog-приложение** — публичный блог с контентом и статическими страницами.

- **Назначение**: публикация статей, новостей и документации.
- **Особенности**:
  - Статический контент в `apps/blog/content/`.
  - Twig-шаблоны для рендеринга страниц.
  - Мультиязычность через переводы.
  - Минимальный набор модулей (только Blog).
- **Модули**: Blog.
- **Типы тестов**: Functional (страницы блога).

## Общий Kernel

Все приложения наследуются от [`Common\Kernel`](src/Kernel.php), который реализует:

- **ModuleKernelTrait** — поддержка модульной системы.
- **MicroKernelTrait** — гибкая конфигурация через PHP.
- **Разделение конфигурации** — общая (`config/`) и приложения (`apps/<app_name>/config/`).
- **Разделение модулей** — общие (`config/modules.php`) и приложения (`apps/<app_name>/config/modules.php`).
- **Разделение кэша и логов** — по идентификатору приложения.

### Пример Kernel приложения

```php
<?php

declare(strict_types=1);

namespace Blog;

use Common\Kernel as CommonKernel;

final class Kernel extends CommonKernel
{
}
```

## Конфигурация приложений

### Регистрация модулей

Модули приложения регистрируются в `apps/<app_name>/config/modules.php`:

```php
<?php

declare(strict_types=1);

return [
    Web\Module\Chat\ChatModule::class => ['all' => true],
    Web\Module\Project\ProjectModule::class => ['all' => true],
];
```

### Регистрация bundles

Bundles приложения регистрируются в `apps/<app_name>/config/bundles.php`:

```php
<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
];
```

### Конфигурация сервисов

Конфигурация сервисов приложения находится в `apps/<app_name>/config/services.yaml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
```

### Маршруты приложения

Маршруты приложения находятся в `apps/<app_name>/config/routes/`:

```yaml
# apps/web/config/routes/dashboard.yaml
dashboard:
    path: /dashboard
    controller: Web\Module\Dashboard\Controller\DashboardController::index
```

## Как используем

- **Создание нового приложения**: создайте директорию `apps/<app_name>/` с необходимой структурой и Kernel, наследуемым от `Common\Kernel`.
- **Добавление модуля в приложение**: зарегистрируйте модуль в `apps/<app_name>/config/modules.php`.
- **Переопределение конфигурации**: создайте файл конфигурации в `apps/<app_name>/config/` для переопределения общих настроек.
- **Разделение тестов**: размещайте тесты в `apps/<app_name>/tests/` для изоляции тестов разных приложений.
- **Разделение кэша и логов**: используйте идентификатор приложения для автоматического разделения директорий.

## Пример

Пример конфигурации модулей для web-приложения (`apps/web/config/modules.php`):

```php
<?php

declare(strict_types=1);

return [
    Web\Module\AppOption\AppOptionModule::class => ['all' => true],
    Web\Module\Attribution\AttributionModule::class => ['all' => true],
    Web\Module\Billing\BillingModule::class => ['all' => true],
    Web\Module\Chat\ChatModule::class => ['all' => true],
    Web\Module\Dashboard\DashboardModule::class => ['all' => true],
    Web\Module\Landing\LandingModule::class => ['all' => true],
    Web\Module\Llm\LlmModule::class => ['all' => true],
    Web\Module\Project\ProjectModule::class => ['all' => true],
    Web\Module\Rag\RagModule::class => ['all' => true],
    Web\Module\Search\SearchModule::class => ['all' => true],
    Web\Module\Source\SourceModule::class => ['all' => true],
    Web\Module\Secret\SecretModule::class => ['all' => true],
    Web\Module\User\UserModule::class => ['all' => true],
    Web\Module\Tag\TagModule::class => ['all' => true],
    Web\Module\Notification\NotificationModule::class => ['all' => true],
];
```

Пример конфигурации модулей для console-приложения (`apps/console/config/modules.php`):

```php
<?php

declare(strict_types=1);

return [
    Console\Module\Chat\ChatModule::class => ['all' => true],
    Console\Module\Llm\LlmModule::class => ['all' => true],
    Console\Module\Project\ProjectModule::class => ['all' => true],
    Console\Module\Rag\RagModule::class => ['all' => true],
    Console\Module\Source\SourceModule::class => ['all' => true],
    Console\Module\SpeechToText\SpeechToTextModule::class => ['all' => true],
    Console\Module\User\UserModule::class => ['all' => true],
    Console\Module\Billing\BillingModule::class => ['all' => true],
    Console\Module\Notification\NotificationModule::class => ['all' => true],
    Console\Module\Fix\FixModule::class => ['all' => true],
];
```

## Чек-лист для проведения ревью кода

- [ ] Приложение имеет правильную структуру директорий.
- [ ] Kernel приложения наследуется от `Common\Kernel`.
- [ ] Модули зарегистрированы в `apps/<app_name>/config/modules.php`.
- [ ] Bundles зарегистрированы в `apps/<app_name>/config/bundles.php`.
- [ ] Конфигурация сервисов находится в `apps/<app_name>/config/services.yaml`.
- [ ] Маршруты приложения находятся в `apps/<app_name>/config/routes/`.
- [ ] Тесты приложения находятся в `apps/<app_name>/tests/`.
- [ ] Нет дублирования модулей между `config/modules.php` и `apps/<app_name>/config/modules.php`.
- [ ] Кэш и логи разделены по идентификатору приложения.
- [ ] Документация соответствует правилам из `docs/conventions/doc-writing-rules.md`.
