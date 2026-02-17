# E2E Source pipeline environment

## Назначение
Этот runbook описывает воспроизводимое E2E окружение для проверки Source pipeline через Symfony Panther,
RabbitMQ и worker-cli, а также команду запуска тестов одним шагом.

**Важно:** E2E окружение полностью изолировано от dev/test окружения через:
- Отдельные compose-файлы (`compose.e2e.yaml`)
- Отдельные volumes (database_data_e2e, rabbitmq_data_e2e, mercure_data_e2e)
- Отдельный RabbitMQ vhost (`task_e2e`)
- Отдельная база данных (`task_e2e_db`)
- Отсутствие host port bindings (нет конфликтов портов)

## Сервисы профиля e2e
Профиль `e2e` поднимает изолированные сервисы:
- `database` (PostgreSQL, isolated volume `database_data_e2e`)
- `php-fpm` (PHP-FPM, isolated volumes)
- `nginx` (web server, no host port binding)
- `rabbitmq` (isolated vhost `task_e2e`, isolated volumes `rabbitmq_data_e2e`, `rabbitmq_logs_e2e`)
- `mercure` (isolated volumes `mercure_data_e2e`, `mercure_config_e2e`)
- `worker-cli` (queue workers, isolated volumes)
- `selenium` (browser automation, no host port binding)
- `test-runner` (PHPUnit execution, isolated volumes)

## Изоляция окружения
E2E окружение использует `APP_ENV=e2e` и загружает конфигурации из:
- `.env.e2e` — базовая конфигурация
- `.env.e2e.local` — локальные переопределения
- `config/packages/e2e/*` — пакет конфигураций для e2e режима

Ключевые отличия от dev/test:
- **APP_ENV**: `e2e` (вместо `test` или `dev`)
- **Database**: `task_e2e_db` (вместо `task_test_db` или `task_dev_db`)
- **RabbitMQ vhost**: `task_e2e` (вместо `task`)
- **Volumes**: Все volumes имеют суффикс `_e2e`
- **Ports**: По умолчанию проброшен только Mailpit UI (`MAILPIT_UI_PUBLIC_PORT`, default `18025`) для ручной проверки писем; остальные e2e сервисы не публикуют host ports.

## Конфигурация окружения
Базовые значения лежат в `.env.e2e`. Локальные переопределения — в `.env.e2e.local`.

Ключевые переменные:
- `APP_ENV=e2e` — режим окружения для Symfony
- `E2E_SOURCE_FILES_DIR_HOST` — путь на host к файлам для загрузки (по умолчанию `./apps/web/tests/E2E/Source/files`).
- `E2E_SOURCE_FILES_DIR` — путь внутри `test-runner` (по умолчанию `/var/www/task/apps/web/tests/E2E/Source/files`).
- `E2E_SOURCE_HTML_URL` — URL для HTML source теста. По умолчанию указывает на локальный fixture внутри e2e-сети:
  `http://nginx/e2e/fixtures/source-pipeline/html-source.html`.
- `PANTHER_BROWSER=selenium` и `PANTHER_SELENIUM_HOST=http://selenium:4444/wd/hub`.
- `PANTHER_EXTERNAL_BASE_URI=http://nginx`.
- `MAILPIT_UI_PUBLIC_PORT=18025` — host port Mailpit UI для ручной проверки email в E2E.
- `E2E_SOURCE_POLL_TIMEOUT_SECONDS` — общий timeout ожиданий (секунды).
- `E2E_SOURCE_POLL_INTERVAL_MS` — базовый интервал polling (миллисекунды).
- `E2E_SOURCE_POLL_MAX_INTERVAL_MS` — максимальный интервал между попытками (миллисекунды).
- `E2E_SOURCE_POLL_BACKOFF_MULTIPLIER` — множитель backoff для интервалов.
- `E2E_SOURCE_POLL_MAX_RETRIES` — лимит попыток (0 = без лимита).
- `PANTHER_ERROR_SCREENSHOT_DIR=/var/www/html/var/e2e-screenshots` — путь для скриншотов (локально `./var/e2e-screenshots`).
- `SOURCE_STATUS_LIVE_DEGRADATION_UI` — режим live-updates payload contract (`off`, `limited`, `on`).
- `E2E_EXPECTED_LIVE_DEGRADATION_MODE` — ожидаемый режим для E2E assertions (обычно равен `SOURCE_STATUS_LIVE_DEGRADATION_UI`).
- `MERCURE_WORKER_PUBLISH_URL` — worker-side publish endpoint для source-status live updates.
- `E2E_FORCE_MERCURE_UNAVAILABLE` — включает non-blocking outage сценарий (значение `1`).

По умолчанию тестовые файлы лежат в `./apps/web/tests/E2E/Source/files` и монтируются в контейнер как
`/var/www/task/apps/web/tests/E2E/Source/files`. Если переменная `E2E_SOURCE_FILES_DIR_HOST` не задана,
используется fallback `./var/e2e-source-files` (не добавляется в git).

HTML fixture для `E2E_SOURCE_HTML_URL` хранится в репозитории по пути
`apps/web/public/e2e/fixtures/source-pipeline/html-source.html` и обслуживается `nginx` сервиса `e2e`.
Это убирает зависимость HTML e2e теста от внешнего интернета и TLS цепочек.

Пример локальной настройки пути (можно использовать путь с пробелами/кириллицей):
```text
E2E_SOURCE_FILES_DIR_HOST="/home/dp/Рабочий стол/Obsidian/TasK/_Тесты/Файлы для обработки"
```

## Fixtures для E2E Source pipeline
Для логина через UI и выбора проекта используйте:
- Username: `e2e-source-pipeline-user`
- Password: `e2e-source-pipeline-password`
- Project label: `E2E Source Pipeline`

Данные загружаются через Doctrine fixtures из `src/DataFixtures/`:
- `UserFixtures` - создаёт E2E пользователя с паролем `e2e-source-pipeline-password`
- `ProjectFixtures` - создаёт проект `E2E Source Pipeline` для E2E пользователя

### Подготовка базы данных с fixtures
Для ручной загрузки fixtures в E2E базу данных:
```bash
make e2e-up
make test-db-fixtures-e2e
```

Команда `test-db-fixtures-e2e`:
1. Создает базу данных `task_e2e_db` (если не существует)
2. Создает расширение `pgvector`
3. Обновляет схему базы данных (`doctrine:schema:update --force`)
4. Загружает Doctrine fixtures через `doctrine:fixtures:load`

Для чистой базы данных (удаление fixtures):
```bash
make e2e-down
podman compose -p task-e2e down --volumes -f compose.e2e.yaml
```

## Запуск
Полный прогон:
```bash
make tests-e2e-source-pipeline
```

Команда:
- поднимает изолированные сервисы профиля `e2e` через `compose.e2e.yaml` с project name `task-e2e` (по умолчанию);
- загружает Doctrine fixtures (`make test-db-fixtures-e2e`);
- запускает `phpunit` с группой `e2e-source-pipeline` в окружении `e2e`;
- проверяет, что очереди `source_*` пустые.

Live-updates полный прогон (`smoke` + `matrix` + `outage`):
```bash
make tests-e2e-source-status-live-updates
```

Live-updates smoke прогон (только проверка source-status live contract):
```bash
make tests-e2e-source-status-live-updates-smoke
```

Matrix прогон feature flag режимов `off/limited/on`:
```bash
make tests-e2e-source-status-live-updates-matrix
```

Non-blocking outage сценарий (Mercure publish-path недоступен):
```bash
make tests-e2e-source-status-live-updates-outage
```

Эта команда проверяет, что `Source` pipeline доходит до `active`, даже если worker не может публиковать live-update в Mercure.

## Запуск в песочницах
Для запуска e2e тестов в изолированной песочнице (например, для параллельных запусков в CI):
```bash
E2E_PROJECT_NAME=task-e2e-pr-1991 make tests-e2e-source-pipeline
```

Это создаст:
- Отдельный проект Docker: `task-e2e-pr-1991` (вместо `task-e2e`)
- Отдельные контейнеры: `task-e2e-pr-1991_database_1`, `task-e2e-pr-1991_php-fpm_1`, ...
- Отдельную сеть: `task-e2e-pr-1991_default`
- Отдельные volumes: именуются с префиксом `task-e2e-pr-1991` (данные изолированы между песочницами)

Использование песочниц полезно для:
- Параллельного запуска e2e тестов в разных PR в CI
- Одновременной разработки в dev и запуска e2e тестов
- Изоляции запусков для отладки

## Preflight проверки
Перед запуском убедитесь, что окружение готово:
```bash
make e2e-up
podman compose -p task-e2e exec test-runner env | rg E2E_SOURCE_
podman compose -p task-e2e exec test-runner env | rg PANTHER_
podman compose -p task-e2e exec test-runner env | rg APP_ENV
podman compose -p task-e2e exec test-runner curl -fsS http://nginx
podman compose -p task-e2e exec worker-cli ffmpeg -version
podman compose -p task-e2e exec worker-cli yt-dlp --version
podman compose -p task-e2e exec worker-cli pdfinfo -v
```

Если использован `E2E_PROJECT_NAME`, замените `task-e2e` на своё значение.

Проверьте, что `APP_ENV=e2e` в окружении test-runner.

## Preflight smoke
Быстрая проверка Panther + selenium + nginx перед тяжёлыми pipeline сценариями:
```bash
make tests-e2e-filter TEST_FILTER=BasicSmokeTest
make tests-e2e-filter TEST_FILTER=SmokeWebTest
```

Ожидаемый результат:
- UI login проходит и виден `#navbarDropdownUser`.
- Страница списка Source отображает таблицу или empty state.
- Форма создания Source содержит ключевые поля.

Если smoke падают:
- проверьте `PANTHER_*` env в `.env.e2e` и доступность `http://nginx` из `test-runner`;
- убедитесь, что запущены `selenium` и `test-runner` профиля `e2e`;
- проверьте, что `APP_ENV=e2e` в окружении.

## Проверка RabbitMQ
Проверка пустоты очередей выполняется скриптом `bin/e2e/check-rabbitmq-queues.sh` внутри `test-runner`.

E2E окружение использует изолированный RabbitMQ vhost `task_e2e`, что предотвращает конфликты
с dev окружением. Очереди `source_*` создаются в vhost `task_e2e`.

Ручная проверка через container:
```bash
podman compose -p task-e2e exec rabbitmq rabbitmqctl -p task_e2e list_queues
```

## Критерии качества
- Целевая длительность полного прогона: до 30 минут.
- Допустимый уровень flaky: не более 1 повторяемого сбоя на 20 прогонов (<= 5%).

## Диагностика live-updates
При падении `SourceStatusLiveUpdatesCrossNodeTest` тест сохраняет артефакты в:

- `var/e2e-screenshots/source-status-live-updates/*.png` — screenshot страницы.
- `var/e2e-screenshots/source-status-live-updates/*.html` — полный HTML DOM.
- `var/e2e-screenshots/source-status-live-updates/*.stream-events.json` — перехваченные `turbo:before-stream-render` события.
- `var/e2e-screenshots/source-status-live-updates/*.log-tail.json` — хвосты worker/php-fpm логов.

Для triage outage сценария дополнительно проверьте:
- `var/containers/e2e/worker-cli/log/e2e.log`
- `var/containers/e2e/worker-cli/log/worker-cli/source_status_live_updates.log`
- `var/containers/e2e/worker-cli/log/worker-cli/source_status_live_updates.error.log`

## Остановка окружения
```bash
make e2e-down
```

Эта команда останавливает и удаляет все E2E контейнеры и services, но сохраняет
изолированные volumes (`database_data_e2e`, `rabbitmq_data_e2e` и т.д.).

## Очистка E2E volumes (при необходимости)
Для полной очистки E2E данных:
```bash
podman compose -p task-e2e down --volumes --remove-orphans -f compose.e2e.yaml
```

**Внимание:** Это удалит все данные E2E окружения (базу данных, очереди RabbitMQ и т.д.).
