# Supervisor сервисы для воркеров TasK

В этом файле приведены готовые шаблоны конфигурации Supervisor для всех воркеров обработки источников.

## Общие параметры

Все сервисы используют:
- Пользователь: `wwwtask`
- Рабочая директория: `/var/www/task`
- Автоматический перезапуск при сбоях
- Логирование в файлы и stdout/stderr

## Установка и настройка Supervisor

### 1. Установка Supervisor

Если Supervisor еще не установлен:

```bash
# Для AlmaLinux/RHEL
sudo dnf install supervisor
```

### 2. Проверка и запуск демона Supervisor

Перед настройкой воркеров необходимо убедиться, что демон Supervisor запущен:

```bash
# Проверка статуса службы Supervisor
sudo systemctl status supervisord

# Если служба не запущена, запустите её
sudo systemctl start supervisord

# Добавьте в автозагрузку при загрузке системы
sudo systemctl enable supervisord
```

### 3. Создание необходимых директорий

```bash
# Создание директории для логов воркеров
sudo mkdir -p /var/log/task/messenger
sudo chown -R wwwtask:wwwtask /var/log/task/messenger
```

## Создание конфигурации воркеров

### 1. Создание файла конфигурации воркеров

На AlmaLinux: основной конфиг `supervisord.conf` лежит в `/etc`, include — в `/etc/supervisord.d/*.ini`.

### Переменные окружения для воркеров

В production TasK читает переменные окружения из `.env*` файлов в корне проекта при запуске `bin/console` (Symfony Dotenv).

Поэтому важно:
- `directory` в каждом `[program:*]` должен указывать на каталог проекта, где лежат `.env*` файлы (в production это `/var/www/task`).
- пользователь `wwwtask` должен иметь права на чтение `.env*` файлов.

Если часть переменных не хранится в `.env*` (или вы сознательно хотите задать их только для одного воркера), используйте параметр `environment=` внутри нужного `[program:*]`:

```ini
[program:source_diarize]
; ...
environment=APP_ENV="prod",APP_DEBUG="0",PYANNOTE_CACHE="/var/www/task/var/.pyannote/.cache/torch/pyannote"
; ...
```

### Рекомендуемые параметры graceful shutdown

Чтобы `supervisorctl stop/restart` корректно завершал `messenger:consume` и не оставлял дочерние процессы (а также чтобы
перезапуск после деплоя был предсказуемым), рекомендуется добавить в каждый `[program:*]`:

```ini
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
```

Где:
- `stopsignal=TERM` — даёт воркеру шанс завершиться “мягко” (Messenger ловит сигнал и останавливается после текущего сообщения);
- `stopwaitsecs` — сколько ждать перед принудительным убийством;
- `stopasgroup/killasgroup` — гарантирует остановку всей process group (важно, если внутри появляются дочерние процессы).

Создайте файл `/etc/supervisord.d/task-workers.ini`:

```ini
[program:source_download]
command=/usr/bin/php /var/www/task/bin/console messenger:consume source_download --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
directory=/var/www/task
user=wwwtask
group=wwwtask
autostart=true
autorestart=true
startretries=5
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/task/messenger/source_download.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stderr_logfile=/var/log/task/messenger/source_download.error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=10

[program:source_extract]
command=/usr/bin/php /var/www/task/bin/console messenger:consume source_extract --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
directory=/var/www/task
user=wwwtask
group=wwwtask
autostart=true
autorestart=true
startretries=5
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/task/messenger/source_extract.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stderr_logfile=/var/log/task/messenger/source_extract.error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=10

[program:source_convert]
command=/usr/bin/php /var/www/task/bin/console messenger:consume source_convert --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
directory=/var/www/task
user=wwwtask
group=wwwtask
autostart=true
autorestart=true
startretries=5
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/task/messenger/source_convert.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stderr_logfile=/var/log/task/messenger/source_convert.error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=10

[program:source_diarize]
command=/usr/bin/php /var/www/task/bin/console messenger:consume source_diarize --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
directory=/var/www/task
user=wwwtask
group=wwwtask
environment=PYANNOTE_CACHE="/var/www/task/var/.pyannote/.cache/torch/pyannote"
autostart=true
autorestart=true
startretries=5
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/task/messenger/source_diarize.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stderr_logfile=/var/log/task/messenger/source_diarize.error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=10

[program:source_transcribe]
command=/usr/bin/php /var/www/task/bin/console messenger:consume source_transcribe --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
directory=/var/www/task
user=wwwtask
group=wwwtask
autostart=true
autorestart=true
startretries=5
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/task/messenger/source_transcribe.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stderr_logfile=/var/log/task/messenger/source_transcribe.error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=10

[program:source_make_document]
command=/usr/bin/php /var/www/task/bin/console messenger:consume source_make_document --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
directory=/var/www/task
user=wwwtask
group=wwwtask
autostart=true
autorestart=true
startretries=5
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/task/messenger/source_make_document.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stderr_logfile=/var/log/task/messenger/source_make_document.error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=10

[program:source_make_document_chunks]
command=/usr/bin/php /var/www/task/bin/console messenger:consume source_make_document_chunks --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
directory=/var/www/task
user=wwwtask
group=wwwtask
autostart=true
autorestart=true
startretries=5
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/task/messenger/source_make_document_chunks.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stderr_logfile=/var/log/task/messenger/source_make_document_chunks.error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=10

[program:source_events]
command=/usr/bin/php /var/www/task/bin/console messenger:consume source_events --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
directory=/var/www/task
user=wwwtask
group=wwwtask
autostart=true
autorestart=true
startretries=5
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/task/messenger/source_events.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stderr_logfile=/var/log/task/messenger/source_events.error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=10

[program:notification_email]
command=/usr/bin/php /var/www/task/bin/console messenger:consume notification_email --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
directory=/var/www/task
user=wwwtask
group=wwwtask
autostart=true
autorestart=true
startretries=5
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/task/messenger/notification_email.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stderr_logfile=/var/log/task/messenger/notification_email.error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=10

[group:task-workers]
programs=source_download,source_extract,source_convert,source_diarize,source_transcribe,source_make_document,source_make_document_chunks,source_events,source_status_live_updates,notification_email
```

### 3. Добавление новых воркеров

> **⚠️ Важно: новые воркеры и переменные *_TRANSPORT_DSN**
>
> При добавлении нового воркера Messenger (нового transport в `config/packages/messenger.yaml`) необходимо добавить соответствующую переменную `*_TRANSPORT_DSN` в `.env.local` **на обоих серверах** (Web и Worker).
>
> **Почему это важно:**
>
> - Переменные `*_TRANSPORT_DSN` в базовом `.env` вычисляются как литеральные значения с `RABBITMQ_HOST=rabbitmq`.
> - Даже если `RABBITMQ_*` переменные переопределены в `.env.local`, `*_TRANSPORT_DSN` уже имеют значение из базового `.env` и не будут автоматически обновлены.
> - **Web-сервер** использует `*_TRANSPORT_DSN` при отправке сообщений в очереди (enqueue).
> - **Worker-сервер** использует `*_TRANSPORT_DSN` при потреблении сообщений из очереди.
> - Если переменная отсутствует в `.env.local`, используется Docker/Podman конфигурация с `RABBITMQ_HOST=rabbitmq`, что приведёт к ошибке `hostname lookup failed`.
>
> **Пример:**
>
> При добавлении воркеров `notification_email` и `source_status_live_updates` в `messenger.yaml`:
>
> ```yaml
> # config/packages/messenger.yaml
> notification_email:
>   dsn: '%env(string:NOTIFICATION_EMAIL_TRANSPORT_DSN)%'
> source_status_live_updates:
>   dsn: '%env(string:SOURCE_STATUS_LIVE_UPDATES_TRANSPORT_DSN)%'
>   # ...
> ```
>
> В `.env.local` на Web и Worker серверах необходимо добавить:
>
> ```bash
> NOTIFICATION_EMAIL_TRANSPORT_DSN="${RABBITMQ_DSN}/notification_email"
> SOURCE_STATUS_LIVE_UPDATES_TRANSPORT_DSN="${RABBITMQ_DSN}/source_status_live_updates"
> ```
>
> **Автоматическая проверка перед перезапуском воркеров:**
>
> ```bash
> # Проверить все *_TRANSPORT_DSN переменные в .env
> grep "_TRANSPORT_DSN=" /var/www/task/.env | cut -d= -f1
>
> # Проверить, какие из них есть в .env.local
> grep "_TRANSPORT_DSN=" /var/www/task/.env.local | cut -d= -f1
>
> # Если переменной нет в .env.local — добавьте её перед перезапуском воркеров
> sudo -u wwwtask nano /var/www/task/.env.local
> ```

### 4. Активация конфигурации воркеров

```bash
# Перезагрузка конфигурации Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Запуск всех воркеров
sudo supervisorctl start task-workers:*

# Проверка статуса
sudo supervisorctl status
```

## Управление воркерами через Supervisor

### Запуск и остановка

```bash
# Запуск всех воркеров
sudo supervisorctl start task-workers:*

# Остановка всех воркеров
sudo supervisorctl stop task-workers:*

# Перезапуск всех воркеров
sudo supervisorctl restart task-workers:*

# Запуск конкретного воркера
sudo supervisorctl start source_download

# Остановка конкретного воркера
sudo supervisorctl stop source_download

# Перезапуск конкретного воркера
sudo supervisorctl restart source_download
```

### Проверка статуса

```bash
# Статус всех воркеров
sudo supervisorctl status

# Статус конкретного воркера
sudo supervisorctl status source_download
```

### Просмотр логов

```bash
# Просмотр логов в реальном времени
sudo supervisorctl tail -f source_download

# Просмотр логов конкретного воркера
sudo tail -f /var/log/task/messenger/source_download.log
sudo tail -f /var/log/task/messenger/source_download.error.log

# Просмотр логов Supervisor
sudo tail -f /var/log/supervisor/supervisord.log
```

## Масштабирование

### Запуск нескольких экземпляров воркера

Для повышения производительности можно запустить несколько экземпляров воркеров:

1. Добавьте в конфигурацию дополнительные программы:

```ini
[program:source_transcribe_2]
command=/usr/bin/php /var/www/task/bin/console messenger:consume source_transcribe --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
directory=/var/www/task
user=wwwtask
group=wwwtask
autostart=true
autorestart=true
startretries=5
stopsignal=TERM
stopwaitsecs=30
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/task/messenger/source_transcribe_2.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stderr_logfile=/var/log/task/messenger/source_transcribe_2.error.log
stderr_logfile_maxbytes=50MB
stderr_logfile_backups=10
```

2. Обновите группу воркеров:

```ini
[group:task-workers]
programs=source_download,source_extract,source_convert,source_diarize,source_transcribe,source_transcribe_2,source_make_document,source_make_document_chunks,source_events,source_status_live_updates,notification_email
```

3. Перезагрузите конфигурацию:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start source_transcribe_2
```

### Рекомендации по масштабированию

- **source_download**: 1-2 воркера (ограничено скоростью скачивания)
- **source_extract**: 1-2 воркера (легковесный)
- **source_transcribe**: 2-4 воркера (требует много CPU/RAM)
- **source_make_document**: 2-4 воркера (требует много CPU/RAM)
- **source_make_document_chunks**: 1-2 воркера (зависит от размера документов)
- **source_events**: 1 воркер (обычно не требует масштабирования)
- **source_status_live_updates**: 1-2 воркера (зависит от fan-out по получателям)
- **notification_email**: 1 воркер (обычно достаточно одного consumer)

## Мониторинг

### Проверка активных воркеров в RabbitMQ

```bash
# Подключитесь к RabbitMQ CLI
rabbitmqctl list_consumers -p task
```

### Скрипт мониторинга

Создайте файл `/usr/local/bin/check-task-supervisor.sh`:

```bash
#!/bin/bash

# Скрипт проверки состояния воркеров TasK через Supervisor

echo "=== Проверка состояния воркеров TasK ==="
echo "Время: $(date)"
echo ""

# Проверка статуса Supervisor
echo "=== Статус Supervisor ==="
sudo supervisorctl status
echo ""

# Проверка потребителей в RabbitMQ
echo "=== Активные потребители в RabbitMQ ==="
rabbitmqctl list_consumers -p task --quiet | wc -l
rabbitmqctl list_consumers -p task
echo ""

# Проверка очередей
echo "=== Состояние очередей ==="
rabbitmqctl list_queues -p task name messages_ready messages_unacknowledged
echo ""

# Проверка свободного места
echo "=== Свободное место на диске ==="
df -h /var/www/task
echo ""

# Проверка загрузки системы
echo "=== Загрузка системы ==="
uptime
free -h
```

Сделайте скрипт исполняемым:

```bash
sudo chmod +x /usr/local/bin/check-task-supervisor.sh
```

Теперь вы можете регулярно проверять состояние воркеров:

```bash
sudo /usr/local/bin/check-task-supervisor.sh
```

## Веб-интерфейс Supervisor (опционально)

Для удобного мониторинга можно включить веб-интерфейс Supervisor:

1. Добавьте в `/etc/supervisord.conf`:

```ini
[inet_http_server]
port=127.0.0.1:9001
username=admin
password=your_secure_password
```

2. Перезапустите Supervisor:

```bash
sudo systemctl restart supervisord
```

3. Доступ к веб-интерфейсу: `http://127.0.0.1:9001`

## Преимущества Supervisor перед systemd

1. **Специализация** - создан специально для управления процессами
2. **Гибкость** - удобнее управлять множеством однотипных процессов
3. **Веб-интерфейс** - удобный мониторинг и управление
4. **Группировка** - можно управлять группами процессов
5. **Единообразие** - тот же подход используется в контейнерах
