# Конфигурация в Symfony

Этот документ описывает общие принципы конфигурирования в Symfony, включая конфигурацию пакетов, окружений, переменных окружения и сервисов.

## Общие принципы конфигурирования в Symfony

**Конфигурирование (Configuration)** — процесс определения поведения приложения через параметры, сервисы, пакеты и окружения. Symfony использует YAML/XML/PHP для конфигурации и поддерживает наследование между окружениями. Подробности: [Symfony Configuration](https://symfony.com/doc/current/configuration.html).

### Общие правила

- Конфигурация хранится в директории `config/` для основного приложения и в `apps/*/config/` для конкретных приложений.
- Используйте YAML для конфигурационных файлов — это стандарт Symfony и наиболее читаемый формат.
- Параметры должны быть именованы с учётом контекста: `module.<module_name>.<context>`, `app.<context>`, `kernel.<context>`.
- Переменные окружения подключаются через `%env()%` и должны быть документированы в `.env.dist`.
- Избегайте хардкода значений в коде — выносите их в конфигурацию.
- Для модулей конфигурация находится в `src/Module/<ModuleName>/Resource/config/`.

### Иерархия конфигурации

Конфигурация Symfony загружается в следующем порядке (поздние значения перезаписывают ранние):

1. `config/packages/*.yaml` — базовая конфигурация пакетов
2. `config/packages/<env>/*.yaml` — окружение-специфичные настройки (dev, test, prod)
3. `apps/*/config/packages/*.yaml` — настройки конкретного приложения
4. `apps/*/config/packages/<env>/*.yaml` — окружение-специфичные настройки приложения

## Конфигурация пакетов (bundles)

**Bundle (Bundle)** — переиспользуемый компонент Symfony, предоставляющий функциональность через сервисы, конфигурацию и ресурсы. Подробности: [Symfony Bundles](https://symfony.com/doc/current/bundles.html).

### Общие правила

- Пакеты регистрируются в `config/bundles.php` для основного приложения и в `apps/*/config/bundles.php` для конкретных приложений.
- Конфигурация пакетов находится в `config/packages/<bundle_name>.yaml` или `apps/*/config/packages/<bundle_name>.yaml`.
- Используйте `when@<environment>` для окружение-специфичных настроек пакета.
- Отключайте ненужные пакеты в тестовом окружении для ускорения выполнения тестов.

### Пример конфигурации пакета

Пример конфигурации Doctrine ORM (`config/packages/doctrine.yaml`):

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        driver: 'pgsql'
        server_version: '16'
        charset: 'utf8'
        default_table_options:
            charset: 'utf8'
            collate: 'utf8_unicode_ci'

    orm:
        auto_generate_proxy_classes: false
        enable_lazy_ghost_objects: true
        report_fields_where_declared: true
        validate_xml_mapping: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            App:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Module'
                prefix: 'Common\Module'
                alias: App

when@test:
    doctrine:
        dbal:
            dbname_suffix: '_test%env(default::TEST_TOKEN)%'

when@prod:
    doctrine:
        orm:
            auto_generate_proxy_classes: false
            proxy_dir: '%kernel.build_dir%/doctrine/orm/Proxies'
            query_cache_driver:
                type: pool
                pool: doctrine.system_cache_pool
            result_cache_driver:
                type: pool
                pool: doctrine.result_cache_pool
```

## Конфигурация окружений (dev, test, prod)

**Окружение (Environment)** — набор конфигурационных настроек для конкретной среды выполнения (разработка, тестирование, продакшн). Подробности: [Symfony Environments](https://symfony.com/doc/current/configuration/environments.html).

### Общие правила

- Symfony поддерживает три основных окружения: `dev`, `test`, `prod`.
- Окружение определяется через переменную окружения `APP_ENV` (по умолчанию `dev`).
- Конфигурация окружения находится в `config/packages/<env>/` и `apps/*/config/packages/<env>/`.
- Используйте `when@<environment>` для условной загрузки конфигурации.
- В `dev` включён профайлер, отладка и подробные логи.
- В `test` используется отдельная тестовая база данных и отключены кэши.
- В `prod` включено кэширование и отключена отладка.

### Пример конфигурации окружений

Пример конфигурации для `dev` окружения (`config/packages/dev/monolog.yaml`):

```yaml
monolog:
    channels:
        - deprecation
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
            channels: ["!event"]
        console:
            type: console
            process_psr_3_messages: false
            channels: ["!event", "!doctrine", "!console"]
```

Пример конфигурации для `prod` окружения (`config/packages/prod/monolog.yaml`):

```yaml
monolog:
    handlers:
        main:
            type: fingers_crossed
            action_level: error
            handler: nested
            excluded_http_codes: [404, 405]
            buffer_size: 50
        nested:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
        console:
            type: console
            process_psr_3_messages: false
            channels: ["!event", "!doctrine"]
        deprecation:
            type: stream
            channels: [deprecation]
            path: "%kernel.logs_dir%/deprecation.log"
```

## Переменные окружения и `.env`

**Переменные окружения (Environment Variables)** — значения, которые передаются в приложение извне и используются в конфигурации. Подробности: [Symfony Environment Variables](https://symfony.com/doc/current/configuration.html#environment-variables).

### Общие правила

- Переменные окружения определяются в файлах `.env`, `.env.local`, `.env.dev`, `.env.test`, `.env.prod`.
- Файл `.env.dist` содержит шаблон с обязательными переменными и их значениями по умолчанию.
- Переменные подключаются через `%env(<TYPE>:<VAR_NAME>)%` в конфигурационных файлах.
- Типы переменных: `string`, `bool`, `int`, `float`, `const`, `base64`, `json`, `url`, `file`, `resolve`.
- `.env.local` игнорируется Git и используется для локальных переопределений.
- Секретные данные (ключи API, пароли) должны храниться в переменных окружения, а не в коде.

### Примеры использования переменных окружения

Пример подключения переменных окружения (`config/packages/framework.yaml`):

```yaml
framework:
    secret: '%env(APP_SECRET)%'
    session:
        handler_id: null
        cookie_secure: auto
        cookie_samesite: lax
        storage_factory_id: 'session.storage.factory.native'
```

Пример файла `.env.dist`:

```bash
###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=a1b2c3d4e5f6g7h8i9j0
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DATABASE_URL="postgresql://task:task@database:5432/task?serverVersion=16&charset=utf8"
###< doctrine/doctrine-bundle ###

###> symfony/messenger ###
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages
###< symfony/messenger ###

###> symfony/mailer ###
MAILER_DSN=smtp://mailer:1025
###< symfony/mailer ###
```

## Конфигурация сервисов

**Сервис (Service)** — объект, который выполняет определённую функцию и управляется через Symfony Service Container. Подробности: [Symfony Service Container](https://symfony.com/doc/current/service_container.html).

### Общие правила

- Сервисы конфигурируются в `services.yaml` через YAML, XML или PHP.
- Используйте `autowire: true` и `autoconfigure: true` для автоматического внедрения зависимостей.
- Параметры сервисов должны быть именованы с учётом контекста: `module.<module_name>.<context>`, `app.<context>`.
- Для импорта каталогов с сервисами используйте `resource` с `exclude` для исключения ненужных файлов.
- Сущности Doctrine не должны быть зарегистрированы как сервисы.
- Интерфейсы репозиториев должны быть внедрены, а реализации — автоматически загружены.

### Пример конфигурации сервисов

Пример конфигурации сервисов модуля (`src/Module/Billing/Resource/config/services.yaml`):

```yaml
parameters:
  module.billing.module_dir: '%kernel.project_dir%/src/Module/Billing'
  module.billing.payment_provider: '%env(BILLING_PAYMENT_PROVIDER)%'

services:
  _defaults:
    autowire: true
    autoconfigure: true

  Common\Module\Billing\:
    resource: '%module.billing.module_dir%/'
    exclude:
      - '%module.billing.module_dir%/Domain/Entity/'
      - '%module.billing.module_dir%/Resource/'
      - '%module.billing.module_dir%/BillingModule.php'

  Common\Module\Billing\Application\Service\PaymentService:
    arguments:
      $paymentProvider: '%module.billing.payment_provider%'

  Common\Module\Billing\Domain\Repository\PaymentRepositoryInterface: '@Common\Module\Billing\Infrastructure\Repository\PaymentRepository'
```

Пример конфигурации сервисов приложения (`config/services.yaml`):

```yaml
parameters:
    app.locale: 'en'
    app.timezone: 'UTC'

services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'

    App\Controller\:
        resource: '../src/Controller/'
        tags: ['controller.service_arguments']
```

## Лучшие практики

### Общие принципы

- **Избегайте хардкода** — все значения, которые могут меняться, выносите в конфигурацию.
- **Используйте параметры** для повторяющихся значений и для документирования конфигурации.
- **Документируйте переменные окружения** в `.env.dist` с комментариями о назначении.
- **Разделяйте конфигурацию** по окружениям — используйте `when@<environment>` для условной загрузки.
- **Следуйте PSR-4** для именования пространств имён и путей к файлам.
- **Используйте автоконфигурацию** — включайте `autowire` и `autoconfigure` для упрощения DI.
- **Исключайте сущности** из автоконфигурации сервисов — Doctrine должна управлять ими сама.
- **Внедряйте интерфейсы** — зависимости должны быть абстракциями, а не конкретными реализациями.

### Безопасность

- **Никогда не коммитьте секреты** в репозиторий — используйте переменные окружения.
- **Используйте `secret:` для чувствительных данных** — Symfony автоматически шифрует их в кэше.
- **Ограничивайте доступ** к конфигурационным файлам на продакшене.
- **Валидируйте переменные окружения** — используйте `validate()` в конфигурации для проверки значений.

### Производительность

- **Включайте кэширование** в `prod` окружении — отключайте в `dev` и `test`.
- **Используйте `pool` для кэшей** — объединяйте похожие кэши в пулы.
- **Отключайте ненужные пакеты** в тестовом окружении для ускорения тестов.
- **Используйте `lazy` для тяжёлых сервисов** — загружайте их только при необходимости.

### Пример конфигурации с лучшими практиками

Пример конфигурации с учётом лучших практик (`config/packages/cache.yaml`):

```yaml
framework:
    cache:
        app: cache.adapter.filesystem
        system: cache.adapter.system
        directory: '%kernel.build_dir%/cache'
        default_redis_provider: '%env(REDIS_URL)%'
        pools:
            doctrine.result_cache_pool:
                adapter: cache.adapter.redis
                provider: '%env(REDIS_URL)%'
            doctrine.system_cache_pool:
                adapter: cache.adapter.system
            app.cache:
                adapter: cache.adapter.filesystem

when@test:
    framework:
        cache:
            app: cache.adapter.array
            system: cache.adapter.array
```

## Чек-лист для проведения ревью конфигурации

- [ ] Конфигурация следует структуре проекта (модули, приложения).
- [ ] Параметры именованы с учётом контекста (`module.<module_name>.<context>`, `app.<context>`).
- [ ] Переменные окружения документированы в `.env.dist`.
- [ ] Сущности исключены из автоконфигурации сервисов.
- [ ] Интерфейсы внедряются, а реализации загружаются автоматически.
- [ ] Окружение-специфичные настройки вынесены в `when@<environment>`.
- [ ] Секретные данные не захардкожены и хранятся в переменных окружения.
- [ ] Кэширование включено в `prod` и отключено в `dev`/`test`.
- [ ] Конфигурация следует правилам из `docs/conventions/doc-writing-rules.md`.
- [ ] Примеры конфигураций на YAML присутствуют и корректны.
