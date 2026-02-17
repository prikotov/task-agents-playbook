# Конфигурирование модулей

Этот документ описывает базовые шаги по подключению модуля к общему ядру проекта и приведению его конфигурации к единым правилам. Прежде чем создавать новый модуль проверьте, что он соответствует DDD-структуре `Domain`, `Application`, `Infrastructure`, `Integration` и что конфигурация лежит в `Resource/config`.

## Базовая структура модуля

1. Создайте класс `<ModuleName>Module` в корне модуля (`src/Module/<ModuleName>/<ModuleName>Module.php`).
2. Реализуйте как минимум `ModuleInterface` и верните пути к каталогу модуля:

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing;

use Common\Component\ModuleSystem\Extension\DoctrineInterface;
use Common\Component\ModuleSystem\ModuleInterface;
use Override;

final class BillingModule implements ModuleInterface, DoctrineInterface
{
    #[Override]
    public function getModuleDir(): string
    {
        return __DIR__;
    }

    #[Override]
    public function getModuleConfigPath(): string
    {
        return $this->getModuleDir() . '/Resource/config';
    }
}
```

3. Если модуль предоставляет Twig-шаблоны, переводы или дополнительные расширения, реализуйте соответствующие интерфейсы (`TwigInterface`, `TranslationInterface` и т.п.). Пример полного списка интерфейсов есть в `Common\Module\Billing\BillingModule`.
4. Добавьте модуль в `config/modules.php`. Для приложений из каталога `apps/*` используйте их собственные `config/modules.php`.

## Конфигурация сервисов

Каждый модуль должен иметь `Resource/config/services.yaml`. В нём объявляются параметры и сервисы модуля. Подробности: [Symfony Service Container](https://symfony.com/doc/current/service_container.html).

- Используйте параметры вида `module.<module_name>.<context>`, чтобы избежать конфликтов имён. Подробности: [Service Parameters](https://symfony.com/doc/current/service_container.html#service-parameters).
- Для импорта каталога с сервисами применяйте `resource: '%module.<module_name>.module_dir%/'` с исключениями (`exclude`) для сущностей и вспомогательных директорий, чтобы Doctrine сама управляла жизненным циклом сущностей. Подробности: [Importing Configuration Files](https://symfony.com/doc/current/service_container/imports.html).
- Значения из переменных окружения подключайте через `%env()%`. Старайтесь документировать обязательные переменные в `.env.dist` или AGENTS.md соответствующего модуля. Подробности: [Environment Variables](https://symfony.com/doc/current/configuration.html#environment-variables).

Пример (`src/Module/Billing/Resource/config/services.yaml`):

```yaml
parameters:
  module.billing.module_dir: '%kernel.project_dir%/src/Module/Billing'

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
```

Такой конфиг подключает все классы модуля и одновременно исключает сущности из автоконфигурации, чтобы ими управлял Doctrine. Подробности: [Autowiring](https://symfony.com/doc/current/service_container/autowiring.html).

## Конфигурация работы с Doctrine-сущностями

> Глава основана на `Common\Module\Billing\BillingModule` и `src/Module/Billing/Resource/config/services.yaml`.

Поддержка Doctrine в модуле включает три шага:

1. **Реализуйте `DoctrineInterface`.** В `<ModuleName>Module` опишите методы:
    - `getEntityNamespace()` — корневое пространство имён сущностей (обычно `__NAMESPACE__ . '\Domain\Entity'`).
    - `getMappingPath()` — путь к каталогу, где лежат классы-сущности. Trait `ModuleKernelTrait` автоматически зарегистрирует этот каталог в DoctrineOrmMappingsPass, если директория существует.

    ```php
    #[Override]
    public function getEntityNamespace(): string
    {
        return __NAMESPACE__ . '\Domain\Entity';
    }

    #[Override]
    public function getMappingPath(): string
    {
        return $this->getModuleDir() . '/Domain/Entity';
    }
    ```

2. **Разместите сущности в `Domain/Entity`.** Сущности оформляются через атрибуты Doctrine, используют постфикс `Model` и технические трейты (`IdTrait`, `UuidTrait`, `InsTsTrait`). Пример можно найти в `Common\Module\Billing\Domain\Entity\PaymentModel`.

3. **Настройте сервисы Doctrine.**
    - Не регистрируйте сущности как сервисы в `services.yaml`. Исключения в разделе `exclude` защищают от автоконфигурации, чтобы Doctrine создавала сущности сама.
    - Репозитории внедряйте через интерфейсы (`Domain\Repository`) и реализации в `Infrastructure\Repository`. Сами реализации автоматически загружаются благодаря `resource` в `services.yaml`.
    - Если модулю нужен отдельный `EntityManager`, добавьте конфигурацию в `Resource/config/doctrine.yaml` (по умолчанию проект использует общий manager, поэтому файл необязателен).

4. **Проверьте миграции.** Создание таблиц для новых сущностей выполняйте через `bin/console make:migration`. Скрипты миграций лежат в корне проекта внутри `migrations/`.

После этих шагов сущности модуля доступны в общем EntityManager. Для тестов создавайте фикстуры или фабрики внутри модуля, а интеграционные тесты наследуйте от `Common\Component\Test\KernelTestCase`, чтобы контейнер модуля полностью инициализировался.

### Чек-лист

- [ ] Модуль реализует `DoctrineInterface` и возвращает корректные пути к сущностям.
- [ ] В `services.yaml` сущности исключены из автоконфигурации.
- [ ] Репозитории регистрируются через интерфейсы и лежат в `Infrastructure\Repository`.
- [ ] Все обязательные параметры описаны и привязаны к `%env()%`.
- [ ] Добавлены миграции для новых таблиц.

## Особенности модулей web-приложения

Web-клиент (`apps/web`) использует тот же модульный подход, но с дополнительными правилами конфигурации:

1. **Отдельный `modules.php`.** Каждый модуль, который должен работать только в web-приложении, регистрируйте в `apps/web/config/modules.php`. Это особенно важно для UI-компонентов, Stimulus-контроллеров и Twig-компонентов, которые не нужны в других приложениях.
2. **Twig и компоненты.** Если модуль предоставляет шаблоны, реализуйте `TwigInterface` в `<ModuleName>Module` и держите шаблоны в `apps/web/src/Module/<ModuleName>/Resource/templates`. Имена, которые будут подключаться как `@web.<module>/...`, формируются автоматически из пространств имён.
3. **Переводы.** Строки, относящиеся к конкретному модулю, размещайте только в `apps/web/src/Module/<ModuleName>/Resource/translations/messages.<locale>.yaml` и подключайте через `TranslationInterface`. Файлы `apps/web/translations/*` предназначены для truly глобальных сообщений (навигация, layout и т.п.). Это правило предотвращает пересечения ключей между модулями.
4. **AssetMapper / Stimulus.** Статические ресурсы и контроллеры должны лежать внутри директории модуля (`Resource/assets`, `Resource/stimulus`). Подключать их нужно через соответствующие конфиги в `Resource/config/assets.yaml` и `Resource/config/stimulus.yaml`, которые импортируются из `apps/web/config/modules.php`.
5. **Тесты UI.** Интеграционные тесты web-модулей размещайте в `apps/web/tests/Integration/Module/<ModuleName>`, чтобы они могли инициализировать нужное приложение и его маршруты.

Следуя этим правилам, переводы и компоненты остаются изолированными внутри модуля, а общие каталоги приложения не зарастают модульным кодом.
