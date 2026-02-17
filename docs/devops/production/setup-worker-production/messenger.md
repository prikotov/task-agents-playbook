# Symfony Messenger и очереди Source

Source pipeline и связанные события обрабатываются через Symfony Messenger. Конфигурация описана в
`config/packages/messenger.yaml` и использует RabbitMQ (см. [../../rabbitmq.md](../../rabbitmq.md)).

## Транспорты

| Транспорт          | Очередь в RabbitMQ  | Назначение                                      |
|--------------------|---------------------|-------------------------------------------------|
| `source_download`  | `source_download`   | Асинхронное скачивание Source                   |
| `source_extract`   | `source_extract`    | Извлечение метаданных и комментариев            |
| `source_transcribe`| `source_transcribe` | Будущие фоновые задачи транскрибации            |
| `source_make_document` | `source_make_document` | Формирование документов RAG                 |
| `source_make_document_chunks` | `source_make_document_chunks` | Подготовка чанков документов         |
| `source_events`    | `source_events`     | Все события `Common\Module\Source\Application\Event` |
| `source_status_live_updates` | `source_status_live_updates` | Live-обновления source status в Mercure |
| `notification_email` | `notification_email` | Асинхронная доставка email уведомлений |
| `failed`           | Doctrine DB         | Отложенное хранилище для непройденных сообщений |

Каждый RabbitMQ transport использует `retry_strategy` и общий DSN `%env(string:RABBITMQ_DSN)%`.
Для `source_status_live_updates` используется более короткий retry budget (2 повтора, 10s -> 20s),
чтобы доставка оставалась в пределах `SOURCE_STATUS_LIVE_UPDATES_MAX_STALENESS_SECONDS`.
Failure transport остаётся на Doctrine, чтобы не терять сообщения при недоступности брокера.

В `.env` транспорты Source по умолчанию привязаны к `${RABBITMQ_DSN}` для очередей `source_download`, `source_extract`, `source_transcribe`, `source_make_document`, `source_make_document_chunks`, `source_events`. Если брокер недоступен (например, на CI без RabbitMQ), можно переопределить их в `.env.local` на `sync://`, чтобы запускать команды синхронно.

Пример `.env.local` для окружений без брокера:

```dotenv
# отключить очереди Source в пользу синхронного исполнения
SOURCE_DOWNLOAD_TRANSPORT_DSN=sync://
SOURCE_EXTRACT_TRANSPORT_DSN=sync://
SOURCE_TRANSCRIBE_TRANSPORT_DSN=sync://
SOURCE_MAKE_DOCUMENT_TRANSPORT_DSN=sync://
SOURCE_MAKE_DOCUMENT_CHUNKS_TRANSPORT_DSN=sync://
SOURCE_EVENTS_TRANSPORT_DSN=sync://
SOURCE_STATUS_LIVE_UPDATES_TRANSPORT_DSN=sync://
NOTIFICATION_EMAIL_TRANSPORT_DSN=sync://
```

CLI `app:source:download` работает синхронно (без Messenger). Асинхронное скачивание инициируется приложением через очередь `source_download`, поэтому воркер RabbitMQ для этой очереди должен быть постоянно запущен.

## Продакшен-воркеры

Запускайте отдельный воркер на каждую очередь, чтобы сбои на одной стадии не блокировали остальные. Базовые команды:

```bash
sudo -u wwwtask php /var/www/task/bin/console messenger:consume source_download --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
sudo -u wwwtask php /var/www/task/bin/console messenger:consume source_extract --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
sudo -u wwwtask php /var/www/task/bin/console messenger:consume source_transcribe --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
sudo -u wwwtask php /var/www/task/bin/console messenger:consume source_make_document --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
sudo -u wwwtask php /var/www/task/bin/console messenger:consume source_make_document_chunks --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
sudo -u wwwtask php /var/www/task/bin/console messenger:consume source_events --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
sudo -u wwwtask php /var/www/task/bin/console messenger:consume source_status_live_updates --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
sudo -u wwwtask php /var/www/task/bin/console messenger:consume notification_email --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
```

Рекомендуется оформить каждый воркер отдельным процессом supervisor. Не забудьте подключить
monitoring/alerts для `failed` очереди.

### Проверка Mercure publisher auth для source-status

Перед запуском live-updates в split-node сценарии выполните probe-команду из worker-контура:

```bash
php /var/www/task/bin/console app:source-status:mercure-auth-check <recipient-uuid> --env=prod
```

Команда проверяет:
- доступность private publish endpoint (`MERCURE_WORKER_PUBLISH_URL`);
- валидность worker publisher JWT (`MERCURE_WORKER_PUBLISHER_JWT_SECRET`);
- возможность публикации в topic scope `SOURCE_STATUS_TOPIC_BASE_URI/*`.

### Переменные окружения для worker’ов

Воркеры используют тот же набор env, что и приложение. Минимально нужно определить:

- `APP_ENV=prod`, `APP_DEBUG=0`
- `APP_SECRET`
- `DATABASE_URL` (подключение к боевой БД)
- `RABBITMQ_DSN` (или набор `RABBITMQ_HOST/PORT/VHOST/USER/PASSWORD` для сборки DSN)

В production переменные обычно хранятся в `.env*` файлах в корне проекта и подтягиваются Symfony при запуске `bin/console`. Если воркеры запускаются на отдельной машине, убедитесь, что рядом с кодом лежит актуальный `.env.local` (или `.env.prod.local`), а в конфиге Supervisor `directory` указывает на каталог проекта, где лежат `.env*` файлы (см. [supervisor-services.md](supervisor-services.md)).

### Отдельные worker’ы пайплайна Source и Notification

Каждую очередь (`source_download`, `source_extract`, `source_transcribe`, `source_make_document`, `source_make_document_chunks`, `source_events`, `source_status_live_updates`, `notification_email`) запускайте в выделенном процессе, чтобы сбой на одном шаге не блокировал остальные.
Подробная настройка Supervisor описана в [supervisor-services.md](supervisor-services.md).

```ini
[Unit]
Description=TasK Messenger worker (QUEUE_NAME)
After=network.target

[Service]
Type=simple
User=wwwtask
WorkingDirectory=/var/www/task
Environment="RABBITMQ_DSN=amqp://task:task@127.0.0.1:5672/task"  # обновите под своё окружение
ExecStart=/usr/bin/php /var/www/task/bin/console messenger:consume QUEUE_NAME --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
StandardOutput=append:/var/log/task/messenger/QUEUE_NAME.log
StandardError=append:/var/log/task/messenger/QUEUE_NAME.error.log
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Повторы и отправку в failure transport обеспечивает конфигурация Messenger (`retry_strategy` + `failure_transport`).
Отслеживайте очереди и таблицу `failed_queue_messages` в мониторинге и RabbitMQ dashboard.

Установка Supervisor для всех воркеров описана в [supervisor-services.md](supervisor-services.md):

```bash
# Создание конфигурации
sudo nano /etc/supervisord.d/task-workers.ini

# Перезагрузка и запуск
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start task-workers:*

# Проверка статуса
sudo supervisorctl status
```

Не забудьте обновить `RABBITMQ_DSN`/пути в unit-файле под своё окружение и создать отдельные unit’ы/логи для остальных очередей.

### Несколько воркеров на одну очередь

Если очередь требует горизонтального масштабирования, запускайте несколько экземпляров `messenger:consume <queue>` — RabbitMQ сам распределит сообщения. Варианты:

1. **Supervisor**: добавьте дополнительные `[program:*]` секции (например, `source_transcribe_2`, `source_transcribe_3`) с той же командой и разными именами логов, затем `supervisorctl reread && supervisorctl update`.
2. **Docker/Podman контейнеры**: запускайте несколько контейнеров с одинаковой командой `messenger:consume <queue>`. Убедитесь, что `.env.local`/env одинаковы.

Следите за `rabbitmqctl list_consumers -p <vhost>` — увидите все активные подключения на очередь.

### Очистка очередей и vhost после экспериментов

Чтобы не накапливать тестовые очереди/сообщения:

1. Посмотрите список vhost и очередей:
   ```bash
   rabbitmqctl list_vhosts
   rabbitmqctl list_queues -p task name messages
   ```
2. Удалите ненужные очереди:
   ```bash
   rabbitmqctl delete_queue -p task source_download
   ```
   Команда удаляет очередь полностью со всеми сообщениями.
3. Если vhost больше не используется, удалите и его:
   ```bash
   rabbitmqctl delete_vhost <vhost-name>
   ```
4. Для чистки пользователей используйте `rabbitmqctl delete_user <user>`.

После удаления не забудьте перезапустить нужные воркеры (systemd/supervisor), чтобы они заново создали рабочие очереди.

### Supervisor (рекомендуемый способ)

Подробная настройка Supervisor для всех воркеров описана в [supervisor-services.md](supervisor-services.md).

Базовый пример конфигурации для одного воркера:

```
[program:task-source-download]
directory=/var/www/task
command=/usr/bin/php /var/www/task/bin/console messenger:consume source_download --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
user=wwwtask
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/task/messenger/source_download.log
stopwaitsecs=10
```

После добавления выполните `supervisorctl reread && supervisorctl update`, затем проверяйте статус `supervisorctl status task-source-download-async`.


## Failure transport

Непрошедшие сообщения попадают в таблицу `failed_queue_messages`. Проверить и повторить их можно штатными командами:

```bash
bin/console messenger:failed:show
bin/console messenger:failed:retry
```

Перед удалением записей убедитесь, что сообщение успешно обработано (через `--force`). Это важно для стадий Source
pipeline, поскольку они затрагивают артефакты и DiskUsage.

## Автоматический перезапуск воркеров при изменениях кода

Для предотвращения ситуации, когда воркеры продолжают выполнять старый код после деплоя, в проект внедрена система контроля версий.

### Компоненты

- **BuildVersionStamp** - штамп, добавляемый к исходящим сообщениям с версией приложения
- **BuildVersionMiddleware** - middleware, добавляющий штамп версии ко всем сообщениям
- **BuildVersionGuardMiddleware** - middleware, проверяющий версию сообщения на стороне воркера
- **BuildVersionProvider** - сервис для получения версии из файла

### Версионирование воркеров

Версия воркера определяется файлом `var/build/version`, который генерируется командой `app:build-version`.

Версия приложения генерируется командой:
```bash
bin/console app:build-version
```

Команда создает файл `var/build/version` с версией в формате `{git-sha}-{timestamp}`.

### Работа системы

1. При отправке сообщения `BuildVersionMiddleware` добавляет `BuildVersionStamp` с текущей версией
2. Воркер при обработке сообщения проверяет версию через `BuildVersionGuardMiddleware`
3. Если версия сообщения новее версии воркера, воркер ставит `RedeliveryStamp`, выбрасывает `BuildVersionMismatchException` (останавливает процесс), сообщение уходит в повторную доставку
4. Supervisor/systemd автоматически перезапускает воркер с новой версией

### Конфигурация

Middleware регистрируются через DI и подключены к `command.bus` (команды из `routing`). Для включения/выключения можно изменить `config/packages/messenger.yaml`:

```yaml
framework:
  messenger:
    buses:
      command.bus:
        middleware:
          - doctrine_ping_connection
          - Common\Infrastructure\Component\Messenger\Middleware\BuildVersionMiddleware
          - Common\Infrastructure\Component\Messenger\Middleware\BuildVersionGuardMiddleware
      query.bus:
        middleware:
          - doctrine_ping_connection
      event.bus:
        default_middleware: allow_no_handlers
        middleware:
          - validation
          - doctrine_ping_connection
```

> События на `event.bus` не штампуются версией. Контроль версии применяется только к командам, отправляемым через `command.bus` и реально маршрутизируемым в транспорт.

### Параметры конфигурации контроля версии воркеров

В `config/packages/app.yaml` задаются параметры для `BuildVersionMiddleware`/`BuildVersionGuardMiddleware`:

- `messenger.worker_code_watch.enabled` — включает/отключает guard (по умолчанию `true`; можно переопределить параметр через конфиг окружения)
- `messenger.worker_code_watch.interval` — интервал проверки версии (по умолчанию `0`, сейчас не используется и зарезервирован под будущие оптимизации)
- `messenger.worker_code_watch.version_file` — путь к файлу версии (по умолчанию `var/build/version`)

`BuildVersionProvider` читает версию из файла. При отсутствии файла или невозможности его прочитать пишется warning‑лог.

### Тестирование

Для проверки работы системы:
1. Сгенерируйте версию: `bin/console app:build-version`
2. Отправьте сообщение в очередь
3. Измените версию: `bin/console app:build-version --build-version=new-version`
4. Запустите воркер - он должен остановиться при обработке сообщения со старой версией
