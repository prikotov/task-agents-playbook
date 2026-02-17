# E2E Tests

End-to-End (E2E) тестирование в проекте выполняется с помощью **Symfony Panther** — библиотеки для тестирования JavaScript-интерактивных сценариев в реальном браузере.

## Оглавление

1. [Обзор](#обзор)
2. [Запуск и Инфраструктура](#запуск-и-инфраструктура)
    - [Команды Makefile](#команды-makefile)
    - [Требования](#требования)
3. [Диагностика и Отладка](#диагностика-и-отладка)
    - [Логи и артефакты](#логи-и-артефакты-файловая-система)
    - [Просмотр логов (CLI)](#просмотр-логов-cli)
    - [Просмотр писем в Mailpit UI](#просмотр-писем-в-mailpit-ui)
4. [Разработка тестов](#разработка-тестов)
    - [Структура файлов](#структура-файлов)
    - [Базовый класс и Клиент](#базовый-класс)
    - [Хелперы (Auth)](#хелперы-и-трейты)
5. [Работа с Panther (API)](#работа-с-panther-api)
    - [Ожидания (Waiting)](#ожидания-waiting)
    - [Выполнение JavaScript](#выполнение-javascript)
6. [Примеры](#примеры-тестов)
7. [Сценарии Smoke и Pipeline](#сценарии-smoke-и-pipeline)
8. [Best Practices](#лучшие-практики)

---

## Обзор

E2E тесты проверяют полное поведение приложения с точки зрения пользователя:
- JavaScript-интерактивность (Turbo, Stimulus, AJAX)
- Навигация и маршрутизацию
- Формы и валидацию
- Модальные окна и диалоги
- Drag & Drop, загрузку файлов
- WebSocket и SSE соединения

### Технологии

| Технология | Назначение |
|-----------|-----------|
| **Symfony Panther** | Запуск тестов в реальном браузере (Chrome/Firefox) |
| **Turbo** | Навигация без перезагрузки страницы |
| **Stimulus** | JavaScript контроллеры |
| **Bootstrap 5 Phoenix** | UI-компоненты и модальные окна |

---

## Запуск и Инфраструктура

Все команды для запуска E2E тестов определены в файле [`devops/make/e2e.mk`](../../devops/make/e2e.mk).

### Команды Makefile

| Команда | Описание |
|---------|-----------|
| `make tests-e2e` | Запустить все E2E тесты (web через selenium + api через WebTestCase) |
| `make tests-e2e-web` | Запустить E2E тесты только для web-приложения (selenium) |
| `make tests-e2e-api` | Запустить E2E тесты только для api-приложения (WebTestCase) |
| `make tests-e2e-filter TEST_FILTER=...` | Запустить по фильтру (имя теста или паттерн) через `test-runner` + `selenium` |
| `make tests-e2e-smoke` | Запустить E2E smoke тесты для быстрой проверки e2e окружения (группа `smoke`) |
| `make tests-e2e-source-pipeline` | Запустить E2E тесты Source pipeline (группа `e2e-source-pipeline`) |
| `make tests-e2e-source-status-live-updates` | Запустить полный E2E live-updates suite (`smoke` + `matrix` + `outage`) |
| `make tests-e2e-source-status-live-updates-smoke` | Запустить только E2E smoke live-updates сценарии (группа `e2e-source-status-live-updates`) |
| `make tests-e2e-source-status-live-updates-matrix` | Прогнать live-updates matrix `off/limited/on` для `SOURCE_STATUS_LIVE_DEGRADATION_UI` |
| `make tests-e2e-source-status-live-updates-outage` | Проверить non-blocking pipeline при недоступном `MERCURE_WORKER_PUBLISH_URL` |
| `make tests-e2e-landing-invite-flow` | Запустить E2E тесты landing invite funnel (группа `e2e-landing-invite-flow`) |
| `make tests-e2e-rabbitmq` | Запустить E2E тесты RabbitMQ (группа `rabbitmq`) |
| `make e2e-up` | Поднять сервисы профиля `e2e` (DB, RabbitMQ, Selenium, etc.) |
| `make e2e-down` | Остановить и удалить сервисы профиля `e2e` |
| `make e2e-clean-host` | Очистить временные файлы (логи, скриншоты, storage) на хосте |

### Примеры использования

```bash
# Запуск всех E2E тестов
make tests-e2e

# Запуск только web-тестов
make tests-e2e-web

# Запуск smoke тестов (быстрая проверка e2e окружения)
make tests-e2e-smoke

# Запуск invite funnel happy-path сценариев
make tests-e2e-landing-invite-flow

# Запуск конкретного теста по имени
make tests-e2e-filter TEST_FILTER=TurboNavigationTest::testTurboNavigation

# Запуск всех тестов в классе
make tests-e2e-filter TEST_FILTER=TurboNavigationTest
```

### Требования и Зависимости

Для запуска E2E тестов используется отдельный профиль docker-compose (`e2e`).
- Команды `make` автоматически поднимают нужные сервисы (`selenium`, `test-runner`, `worker-cli`).
- Фикстуры накатываются автоматически перед запуском тестов.
- `make tests-e2e` запускает web-suite через `test-runner` + `selenium`, а затем api-suite через `php-test`.

---

## Диагностика и Отладка

Для разбора проблемных ситуаций (особенно в CI или при локальном запуске в контейнерах) предусмотрен ряд инструментов и локаций с логами.

### Логи и артефакты (Файловая система)

Все важные данные монтируются из контейнеров в папку `var/` на хосте:

| Путь на хосте | Описание | Контейнер |
|---------------|----------|-----------|
| `var/containers/e2e/php-fpm/log/` | Логи backend приложения (dev.log, error.log) | `php-fpm` |
| `var/containers/e2e/worker-cli/log/` | Логи асинхронных воркеров | `worker-cli` |
| `var/containers/e2e/test-runner/log/` | Логи выполнения тестов | `test-runner` |
| `var/e2e-screenshots/` | Скриншоты и HTML страниц при падении тестов | `test-runner` / `selenium` |
| `var/containers/e2e/shared/storage/` | Загруженные файлы и сгенерированные документы | `php-fpm`, `worker-cli` |

> **Примечание:** Логи **Nginx** не сохраняются в файлы на хосте, их можно посмотреть только через команду `podman logs task-e2e-nginx-1`.
>
> **Важно:** Команда `make e2e-clean-host` очищает эти директории. Если вам нужно сохранить артефакты для дебага, не запускайте clean-команды сразу после падения.

### Просмотр логов (CLI)

Контейнеры E2E окружения имеют префикс проекта `task-e2e`.
Вы можете смотреть логи запущенных контейнеров с помощью `podman logs` или `docker logs`.

```bash
# Логи backend приложения (PHP) - ошибки 500, исключения
podman logs task-e2e-php-fpm-1

# Логи веб-сервера (Nginx) - access logs, коды ответов
podman logs task-e2e-nginx-1

# Логи worker обработки очередей (важно для Source Pipeline)
podman logs task-e2e-worker-cli-1

# Логи выполнения тестов (PHPUnit output)
podman logs task-e2e-test-runner-1

# Логи браузера Selenium (Chrome) - ошибки драйвера
podman logs task-e2e-selenium-1

# Логи PostgreSQL и RabbitMQ
podman logs task-e2e-database-1
podman logs task-e2e-rabbitmq-1
```

### Просмотр писем в Mailpit UI

Для ручной проверки отправленных писем в E2E окружении используется Mailpit Web UI:

- URL на хосте: `http://localhost:18025`
- Порт задаётся переменной `MAILPIT_UI_PUBLIC_PORT` в `.env.e2e` (по умолчанию `18025`)
- Внутри сети контейнеров API Mailpit доступно по `http://mailer:8025`

Проверка, что UI доступен:

```bash
curl -fsS http://localhost:18025/api/v1/messages | jq
```

---

## Разработка тестов

### Структура файлов

```
apps/
├── web/
│   └── tests/
│       ├── Base/
│       │   └── PantherWebTestCase.php    # Базовый класс для E2E тестов
│       ├── E2E/
│       │   └── JavaScript/
│       │       └── TurboNavigationTest.php   # Примеры E2E тестов
│       └── Support/
│           └── Trait/
│               └── UserLoginTrait.php     # Хелпер для логина в E2E
└── api/
    └── tests/
        └── E2E/
            └── ...                        # API E2E тесты
```

### Базовый класс

Все E2E тесты наследуются от [`PantherWebTestCase`](../../apps/web/tests/Base/PantherWebTestCase.php):

```php
namespace Web\Test\E2E\JavaScript;

use Web\Test\Base\PantherWebTestCase;
use Web\Test\Support\Trait\UserLoginTrait;

final class TurboNavigationTest extends PantherWebTestCase
{
    use UserLoginTrait;

    public function testTurboNavigation(): void
    {
        // Создание клиента с подключением к nginx (рекомендуется)
        $client = self::createPantherClient(['external_base_uri' => 'http://nginx']);
        
        $crawler = $client->request('GET', '/');
        // ...
    }
}
```

### Хелперы и трейты

Трейт [`UserLoginTrait`](../../apps/web/tests/Support/Trait/UserLoginTrait.php) упрощает авторизацию пользователя в E2E тестах:

```php
use Web\Test\Support\Trait\UserLoginTrait;

final class MyE2ETest extends PantherWebTestCase
{
    use UserLoginTrait;

    public function testProtectedPage(): void
    {
        $client = self::createPantherClient();
        $this->loginMainUser($client);

        // Теперь пользователь авторизован и можно переходить к защищённым страницам
        $crawler = $client->request('GET', '/dashboard');
    }
}
```

---

## Работа с Panther (API)

### Ожидания (Waiting)

Это **самая важная часть** E2E тестов. Panther предоставляет мощные методы ожидания для JavaScript-сценариев. Никогда не используйте `sleep()`, используйте `waitFor*`.

| Метод | Описание |
|-------|----------|
| `waitFor($callback)` | Ожидание, пока callback не вернёт true |
| `waitForElementToContain($selector, $text)` | Ожидание, пока элемент не будет содержать текст |
| `waitForVisibility($selector)` | Ожидание видимости элемента |
| `waitForStaleness($selector)` | Ожидание исчезновения элемента из DOM |
| `waitForElementToContain($selector, $text)` | Ожидание появления текста в элементе |

```php
// Ожидание по условию
$client->waitFor(function () use ($client) {
    return $client->getCrawler()->filter('.loaded')->count() > 0;
});

// Ожидание текста
$client->waitForElementToContain('h1', 'Dashboard');

// Ожидание видимости
$client->waitForVisibility('.dashboard-content');
```

### Выполнение JavaScript

```php
// Выполнение JavaScript и возврат результата
$title = $client->executeScript('return document.title;');

// Модификация DOM
$client->executeScript('document.body.setAttribute("data-test", "e2e");');

// Работа с LocalStorage
$client->executeScript('localStorage.setItem("testKey", "testValue");');
```

---

## Примеры тестов

Файл [`apps/web/tests/E2E/JavaScript/TurboNavigationTest.php`](../../apps/web/tests/E2E/JavaScript/TurboNavigationTest.php) содержит исчерпывающие примеры:

#### Пример теста Turbo навигации

```php
public function testTurboNavigation(): void
{
    $client = self::createPantherClient(['external_base_uri' => 'http://nginx']);

    // Открываем страницу с Turbo навигацией
    $crawler = $client->request('GET', '/');

    // Ждём загрузки страницы (JavaScript должен выполниться)
    $client->waitForElementToContain('html body', 'Home');

    // Находим ссылку с Turbo и кликаем по ней
    $link = $crawler->selectLink('Projects')->link();
    $client->click($link);

    // Ждём завершения Turbo навигации (JavaScript)
    $client->waitForElementToContain('h1', 'Projects');

    // Проверяем, что навигация произошла успешно
    $this->assertSelectorTextContains('h1', 'Projects');
}
```

#### Пример теста с Stimulus контроллером

```php
public function testStimulusControllerInteraction(): void
{
    $client = self::createPantherClient();
    $this->loginMainUser($client);
    $crawler = $client->request('GET', '/dashboard');

    // Выполняем JavaScript для взаимодействия с контроллером
    $client->executeScript("document.querySelector('[data-controller=\"example\"]').dispatchEvent(new Event('example:click'));");

    // Ждём реакции контроллера
    $client->waitForElementToContain('[data-controller="example"]', 'Action completed');
}
```

---

## Сценарии Smoke и Pipeline

Smoke E2E используются как быстрый пред-проверочный шаг перед тяжёлыми pipeline сценариями.

### Особенности запуска
- `make tests-e2e-source-pipeline`
- `make tests-e2e-landing-invite-flow`
- Для web smoke используется профиль `e2e` с `test-runner` + `selenium`.
- `PantherWebTestCase` по умолчанию использует `external_base_uri=http://nginx`.

### Обязательные ENV переменные (см. `.env.e2e`)
Эти переменные критичны для работы в изолированном окружении:
- `PANTHER_APP_ENV=test`
- `PANTHER_BROWSER=selenium`
- `PANTHER_SELENIUM_HOST=http://selenium:4444/wd/hub`
- `PANTHER_EXTERNAL_BASE_URI=http://nginx`
- `PANTHER_ERROR_SCREENSHOT_DIR=/var/www/html/var/e2e-screenshots`

---

## Лучшие практики

1. **Anti-Flaky стратегии:**
   - **Стабильные селекторы:** Предпочитайте `id`, `name`, `role` или специальные `data-test` атрибуты. Избегайте хрупких CSS путей.
   - **Явные ожидания:** Для Turbo/JS всегда используйте `waitFor(...)` вместо фиксированных `sleep()`.
   - **Маркеры завершения:** Ожидайте появления конкретного элемента (например, `#success-message`), который гарантирует завершение асинхронной операции.

2. **Изоляция:**
   - **External URI:** Используйте `external_base_uri => 'http://nginx'` для корректной работы JS (Turbo/Stimulus).
   - **Фикстуры:** Каждый тест должен полагаться на предсказуемое состояние БД (используйте `test-db-fixtures-e2e`).

3. **Отладка:**
   - Если тест падает по таймауту, первым делом проверьте скриншот в `var/e2e-screenshots`.
   - Увеличивайте таймауты через опции клиента для медленных операций:
     ```php
     $client = self::createPantherClient([
         'connection_timeout_in_ms' => 60000,
         'request_timeout_in_ms' => 60000,
     ]);
     ```

---

## Ссылки

- [Symfony Panther Documentation](https://symfony.com/doc/current/testing/panther.html)
- [Turbo Handbook](https://turbo.hotwired.dev/handbook/drive)
- [Stimulus Handbook](https://stimulus.hotwired.dev/)
