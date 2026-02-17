# Guidelines: Tests

Эти рекомендации основаны на официальной документации Symfony: https://symfony.com/doc/current/testing.html

- Unit тесты размещайте в каталоге `tests/Unit`, повторяя структуру `src/`.
- Integration тесты размещайте в каталоге `tests/Integration`, повторяя структуру `src/`.
- Используйте пространство имен `Common\Test\...` для всех тестов (например, `Common\Test\Unit\Module\...`).
- Все интеграционные и функциональные тесты **обязаны** наследоваться от `Common\Component\Test\KernelTestCase`. Это гарантирует корректный запуск ядра Symfony и доступ к сервисам контейнера.
- Для инициализации ядра используйте `self::bootKernel()`. Ядро перезапускается перед каждым тестом, что обеспечивает изоляцию.
- Тесты выполняются в окружении `test`, загружая конфигурацию из `config/packages/test/`.
- Соблюдайте общие правила из корневого `AGENTS.md` по стилю кода и покрытию тестами.

## Integration tests: порядок запуска (php-test)

1. Обычный прогон (рекомендуется):
   - `make tests-integration`
2. Прогон с фикстурами:
   - `make tests-integration-fixtures` (сам выполняет `make test-db-fixtures`)
3. Точечный прогон по пути:
   - `make tests-integration-path TEST_PATH=apps/web/tests/Integration/Module/User/Controller/User/EditControllerTest.php`
4. Точечный прогон по filter:
   - `make tests-integration-filter TEST_FILTER=EditControllerTest`
5. Точечный прогон по group:
   - `make tests-integration-group TEST_GROUP=security`

> По умолчанию предполагается, что test DB уже подготовлена. Если нужно обновить схему или загрузить фикстуры — используйте `make test-db-prepare` или `make test-db-fixtures`.

### Прямой запуск через php-test (опционально)

Используйте контейнер `php-test` из compose profile `test` только если нужно запустить уникальную команду, которой нет в `make`.

## E2E tests: порядок запуска

E2E (End-to-End) тесты проверяют полное поведение приложения с точки зрения пользователя, включая JavaScript-интерактивность (Turbo, Stimulus, AJAX).

### Выбор инструмента: WebTestCase vs PantherTestCase

Для E2E тестирования в проекте используются два подхода:

#### WebTestCase (для API)
Используйте `WebTestCase` через `ApiTestCase` для API E2E тестов.

**Когда использовать:**
- Стандартные REST endpoints без JavaScript
- JWT аутентификация, JSON request/response
- HTTP методы (GET, POST, PUT, DELETE, PATCH)
- Проверка статусов ответов, заголовков, тела JSON ответа

**Почему WebTestCase для API:**
- API endpoints не содержат JavaScript-функционала
- Не требуется реальный браузер
- Быстрее и проще в использовании
- Полностью покрывает потребности REST API тестирования

**Пример:** `apps/api/tests/E2E/Auth/AuthFlowTest.php` наследует `ApiTestCase` (WebTestCase)

#### PantherTestCase (для Web)
Используйте `PantherTestCase` через `PantherWebTestCase` для web E2E тестов.

**Когда использовать:**
- Тестирование через реальный браузер
- JavaScript-интерактивность (Turbo, Stimulus, AJAX)
- Проверка работы frontend-фреймворков
- CORS, cookies, browser-specific headers
- Снимки экранов для отладки

**Пример:** `apps/web/tests/E2E/JavaScript/TurboNavigationTest.php` наследует `PantherWebTestCase`

**Нельзя:** Не используйте Panther для тестирования чистого API без JavaScript — это избыточно.

### Команды Makefile

1. Запуск всех E2E тестов:
   - `make tests-e2e` (web + api через `test-runner`)
2. Запуск только для web-приложения:
   - `make tests-e2e-web` (selenium + `test-runner`)
3. Запуск только для api-приложения:
   - `make tests-e2e-api` (через `test-runner`)
4. Запуск по фильтру:
   - `make tests-e2e-filter TEST_FILTER=TurboNavigationTest`
5. Специальные наборы:
   - `make tests-e2e-source-pipeline` (Source pipeline сценарии)
   - `make tests-e2e-rabbitmq` (проверка интеграции с очередями)
6. Управление окружением:
   - `make e2e-up` / `make e2e-down` (поднять/опустить сервисы)
   - `make e2e-clean-host` (очистка логов и скриншотов)

`tests-e2e-*` команды используют профиль `e2e` и поднимают сервисы автоматически.

### Диагностика и отладка

При падении тестов проверяйте:
- **Скриншоты:** `var/e2e-screenshots/`
- **Логи:** `var/containers/e2e/php-fpm/log/` (логи приложений) и `var/containers/e2e/worker-cli/log/` (логи воркеров).
- **Очереди:** RabbitMQ Management UI на `http://localhost:15672`.

Подробная инструкция по диагностике: [`docs/testing/e2e.md`](../docs/testing/e2e.md)

### Базовые классы

- **Web E2E:** Все E2E тесты web-приложения наследуются от `Web\Test\Base\PantherWebTestCase` и должны использовать `external_base_uri => 'http://nginx'` для корректной работы JavaScript.
- **API E2E:** Все E2E тесты API приложения наследуются от `Api\Test\ApiTestCase` (WebTestCase).

### Примеры

Полная документация и примеры тестов: [`docs/testing/e2e.md`](../docs/testing/e2e.md)

Пример теста с JavaScript-интерактивностью: [`apps/web/tests/E2E/JavaScript/TurboNavigationTest.php`](../apps/web/tests/E2E/JavaScript/TurboNavigationTest.php) (13 методов демонстрируют работу с Turbo, Stimulus, AJAX и другими JS-фреймворками).

Пример API E2E теста: [`apps/api/tests/E2E/Auth/AuthFlowTest.php`](../apps/api/tests/E2E/Auth/AuthFlowTest.php) (демонстрирует JWT auth, JSON request/response, проверку статусов HTTP).
