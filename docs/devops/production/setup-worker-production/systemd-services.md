# Systemd сервисы для воркеров TasK  (не используется, см. [supervisor-services.md](supervisor-services.md))

В этом файле приведены готовые шаблоны systemd-юнитов для всех воркеров обработки источников.

## Общие параметры

Все сервисы используют:
- Пользователь: `wwwtask`
- Рабочая директория: `/var/www/task`
- Переменные окружения: Symfony Runtime автоматически подхватывает `.env*` из `/var/www/task` (обычно достаточно `.env.local` или `.env.local.php`)
- Интервал ожидания (Messenger `--sleep`): `1 секунда`
- Пауза перед рестартом (systemd `RestartSec`): `5 секунд`
- Лимит памяти (Messenger `--memory-limit`): по умолчанию `512M` (для тяжёлых воркеров — `1G`)
- Автоматический перезапуск при сбоях

## Предварительная подготовка

1. Создайте директорию логов и назначьте права:

```bash
sudo install -d -o wwwtask -g wwwtask /var/log/task/messenger
```

2. Проверьте, что файл окружения существует и читается пользователем `wwwtask`:

```bash
sudo test -r /var/www/task/.env.local
```

> Примечание: `StandardOutput=append:/path/to/file` требует достаточно свежей версии systemd. Если сервис не стартует из-за настроек логов, замените строки `StandardOutput`/`StandardError` на `StandardOutput=journal` и смотрите логи через `journalctl -u <unit> -f`.

## Шаблон для создания сервисов

### 1. source_download

Создайте файл `/etc/systemd/system/task-worker-source-download.service`:

```ini
[Unit]
Description=TasK Messenger worker (source_download)
After=network-online.target rabbitmq-server.service
Wants=network-online.target

[Service]
Type=simple
User=wwwtask
Group=wwwtask
WorkingDirectory=/var/www/task
ExecStart=/usr/bin/php /var/www/task/bin/console messenger:consume source_download --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
StandardOutput=append:/var/log/task/messenger/source_download.log
StandardError=append:/var/log/task/messenger/source_download.error.log
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### 2. source_extract

Создайте файл `/etc/systemd/system/task-worker-source-extract.service`:

```ini
[Unit]
Description=TasK Messenger worker (source_extract)
After=network-online.target rabbitmq-server.service
Wants=network-online.target

[Service]
Type=simple
User=wwwtask
Group=wwwtask
WorkingDirectory=/var/www/task
ExecStart=/usr/bin/php /var/www/task/bin/console messenger:consume source_extract --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
StandardOutput=append:/var/log/task/messenger/source_extract.log
StandardError=append:/var/log/task/messenger/source_extract.error.log
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### 3. source_convert

Создайте файл `/etc/systemd/system/task-worker-source-convert.service`:

```ini
[Unit]
Description=TasK Messenger worker (source_convert)
After=network-online.target rabbitmq-server.service
Wants=network-online.target

[Service]
Type=simple
User=wwwtask
Group=wwwtask
WorkingDirectory=/var/www/task
ExecStart=/usr/bin/php /var/www/task/bin/console messenger:consume source_convert --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
StandardOutput=append:/var/log/task/messenger/source_convert.log
StandardError=append:/var/log/task/messenger/source_convert.error.log
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### 4. source_diarize

Создайте файл `/etc/systemd/system/task-worker-source-diarize.service`:

```ini
[Unit]
Description=TasK Messenger worker (source_diarize)
After=network-online.target rabbitmq-server.service
Wants=network-online.target

[Service]
Type=simple
User=wwwtask
Group=wwwtask
WorkingDirectory=/var/www/task
ExecStart=/usr/bin/php /var/www/task/bin/console messenger:consume source_diarize --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
StandardOutput=append:/var/log/task/messenger/source_diarize.log
StandardError=append:/var/log/task/messenger/source_diarize.error.log
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### 5. source_transcribe

Создайте файл `/etc/systemd/system/task-worker-source-transcribe.service`:

```ini
[Unit]
Description=TasK Messenger worker (source_transcribe)
After=network-online.target rabbitmq-server.service
Wants=network-online.target

[Service]
Type=simple
User=wwwtask
Group=wwwtask
WorkingDirectory=/var/www/task
ExecStart=/usr/bin/php /var/www/task/bin/console messenger:consume source_transcribe --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
StandardOutput=append:/var/log/task/messenger/source_transcribe.log
StandardError=append:/var/log/task/messenger/source_transcribe.error.log
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### 6. source_make_document

Создайте файл `/etc/systemd/system/task-worker-source-make-document.service`:

```ini
[Unit]
Description=TasK Messenger worker (source_make_document)
After=network-online.target rabbitmq-server.service
Wants=network-online.target

[Service]
Type=simple
User=wwwtask
Group=wwwtask
WorkingDirectory=/var/www/task
ExecStart=/usr/bin/php /var/www/task/bin/console messenger:consume source_make_document --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
StandardOutput=append:/var/log/task/messenger/source_make_document.log
StandardError=append:/var/log/task/messenger/source_make_document.error.log
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### 7. source_make_document_chunks

Создайте файл `/etc/systemd/system/task-worker-source-make-document-chunks.service`:

```ini
[Unit]
Description=TasK Messenger worker (source_make_document_chunks)
After=network-online.target rabbitmq-server.service
Wants=network-online.target

[Service]
Type=simple
User=wwwtask
Group=wwwtask
WorkingDirectory=/var/www/task
ExecStart=/usr/bin/php /var/www/task/bin/console messenger:consume source_make_document_chunks --env=prod --no-interaction --sleep=1 --memory-limit=1G --verbose
StandardOutput=append:/var/log/task/messenger/source_make_document_chunks.log
StandardError=append:/var/log/task/messenger/source_make_document_chunks.error.log
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

### 8. source_events

Создайте файл `/etc/systemd/system/task-worker-source-events.service`:

```ini
[Unit]
Description=TasK Messenger worker (source_events)
After=network-online.target rabbitmq-server.service
Wants=network-online.target

[Service]
Type=simple
User=wwwtask
Group=wwwtask
WorkingDirectory=/var/www/task
ExecStart=/usr/bin/php /var/www/task/bin/console messenger:consume source_events --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose
StandardOutput=append:/var/log/task/messenger/source_events.log
StandardError=append:/var/log/task/messenger/source_events.error.log
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

После добавления/изменения unit-файлов выполните:

```bash
sudo systemctl daemon-reload
```

## Скрипт для автоматического создания сервисов

Создайте файл `/usr/local/bin/setup-task-workers.sh`:

```bash
#!/bin/bash

# Скрипт для создания и настройки systemd сервисов воркеров TasK

WORKER_DIR="/var/www/task"
LOG_DIR="/var/log/task/messenger"
USER_NAME="wwwtask"

# Проверка, что скрипт запущен от root
if [[ $EUID -ne 0 ]]; then
   echo "Этот скрипт должен быть запущен от root (используйте sudo)"
   exit 1
fi

# Создание директории для логов
mkdir -p "${LOG_DIR}"
chown "${USER_NAME}:${USER_NAME}" "${LOG_DIR}"

# Список воркеров
WORKERS=(
    "source_download"
    "source_extract"
    "source_convert"
    "source_diarize"
    "source_transcribe"
    "source_make_document"
    "source_make_document_chunks"
    "source_events"
)

# Функция создания сервиса
create_service() {
    local worker=$1
    local memory_limit=${2:-512M}
    local worker_unit=${worker//_/-}
    
    cat > "/etc/systemd/system/task-worker-${worker_unit}.service" << EOF
[Unit]
Description=TasK Messenger worker (${worker})
After=network-online.target rabbitmq-server.service
Wants=network-online.target

[Service]
Type=simple
User=${USER_NAME}
Group=${USER_NAME}
WorkingDirectory=${WORKER_DIR}
ExecStart=/usr/bin/php ${WORKER_DIR}/bin/console messenger:consume ${worker} --env=prod --no-interaction --sleep=1 --memory-limit=${memory_limit} --verbose
StandardOutput=append:${LOG_DIR}/${worker}.log
StandardError=append:${LOG_DIR}/${worker}.error.log
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    echo "Создан сервис task-worker-${worker_unit}"
}

# Создание сервисов
for worker in "${WORKERS[@]}"; do
    # Для воркеров с высокой нагрузкой увеличиваем лимит памяти
    if [[ $worker == "source_transcribe" ]] || [[ $worker == "source_convert" ]] || [[ $worker == "source_diarize" ]] || [[ $worker == source_make_document* ]]; then
        create_service "${worker}" 1G
    else
        create_service "${worker}" 512M
    fi
done

# Перезагрузка systemd
systemctl daemon-reload

# Активация сервисов
for worker in "${WORKERS[@]}"; do
    worker_unit=${worker//_/-}
    systemctl enable "task-worker-${worker_unit}"
    echo "Сервис task-worker-${worker_unit} добавлен в автозапуск"
done

echo ""
echo "Все сервисы созданы и добавлены в автозапуск."
echo "Для запуска выполните: systemctl start task-worker-source-{download,extract,convert,diarize,transcribe,make-document,make-document-chunks,events}"
echo "Для проверки статуса: systemctl status task-worker-*"
```

Сделайте скрипт исполняемым:

```bash
sudo chmod +x /usr/local/bin/setup-task-workers.sh
```

## Управление сервисами

### Запуск всех воркеров

```bash
# Запуск всех воркеров
systemctl start task-worker-source-{download,extract,convert,diarize,transcribe,make-document,make-document-chunks,events}

# Или по одному
systemctl start task-worker-source-download
systemctl start task-worker-source-extract
systemctl start task-worker-source-convert
systemctl start task-worker-source-diarize
systemctl start task-worker-source-transcribe
systemctl start task-worker-source-make-document
systemctl start task-worker-source-make-document-chunks
systemctl start task-worker-source-events
```

### Проверка статуса

```bash
# Статус всех воркеров
systemctl status task-worker-*

# Статус конкретного воркера
systemctl status task-worker-source-download
```

### Просмотр логов

```bash
# Логи конкретного воркера
journalctl -u task-worker-source-download -f

# Файловые логи
tail -f /var/log/task/messenger/source_download.log
tail -f /var/log/task/messenger/source_download.error.log
```

### Перезапуск

```bash
# Перезапуск всех воркеров
systemctl restart task-worker-source-{download,extract,convert,diarize,transcribe,make-document,make-document-chunks,events}

# Перезапуск конкретного воркера
systemctl restart task-worker-source-download
```

## Масштабирование

### Запуск нескольких экземпляров воркера

Для повышения производительности можно запустить несколько экземпляров воркеров для одной очереди:

#### Вариант 1: Использование template-юнитов

1. Переименуйте сервис, добавив `@` в конец имени:
   ```bash
   mv /etc/systemd/system/task-worker-source-download.service /etc/systemd/system/task-worker-source-download@.service
   ```

2. Замените в файле `task-worker-source-download@.service`:
   ```ini
   Description=TasK Messenger worker (source_download-%i)
   StandardOutput=append:/var/log/task/messenger/source_download-%i.log
   StandardError=append:/var/log/task/messenger/source_download-%i.error.log
   ```

3. Запустите несколько экземпляров:
   ```bash
   systemctl enable --now task-worker-source-download@1
   systemctl enable --now task-worker-source-download@2
   systemctl enable --now task-worker-source-download@3
   ```

#### Вариант 2: Копирование сервисов

1. Скопируйте сервис с новым именем:
   ```bash
   cp /etc/systemd/system/task-worker-source-download.service /etc/systemd/system/task-worker-source-download-2.service
   ```

2. Измените в копии пути к логам:
   ```ini
   Description=TasK Messenger worker (source_download-2)
   StandardOutput=append:/var/log/task/messenger/source_download-2.log
   StandardError=append:/var/log/task/messenger/source_download-2.error.log
   ```

3. Активируйте новый сервис:
   ```bash
   systemctl daemon-reload
   systemctl enable --now task-worker-source-download-2
   ```

### Рекомендации по масштабированию

- **source_download**: 1-2 воркера (ограничено скоростью скачивания)
- **source_extract**: 1-2 воркера (легковесный)
- **source_convert**: 1-2 воркера (может требовать CPU/RAM)
- **source_diarize**: 1-2 воркера (требует CPU/RAM)
- **source_transcribe**: 2-4 воркера (требует много CPU/RAM)
- **source_make_document**: 2-4 воркера (требует много CPU/RAM)
- **source_make_document_chunks**: 1-2 воркера (зависит от размера документов)
- **source_events**: 1 воркер (обычно не требует масштабирования)

## Мониторинг

### Проверка активных воркеров в RabbitMQ

```bash
# Подключитесь к RabbitMQ CLI
rabbitmqctl list_consumers -p task
```

### Скрипт мониторинга

Создайте файл `/usr/local/bin/check-task-workers.sh`:

```bash
#!/bin/bash

# Скрипт проверки состояния воркеров TasK

echo "=== Проверка состояния воркеров TasK ==="
echo "Время: $(date)"
echo ""

# Проверка systemd сервисов
echo "=== Статус systemd сервисов ==="
systemctl is-active task-worker-source-{download,extract,convert,diarize,transcribe,make-document,make-document-chunks,events}
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
sudo chmod +x /usr/local/bin/check-task-workers.sh
```

Теперь вы можете регулярно проверять состояние воркеров:

```bash
sudo /usr/local/bin/check-task-workers.sh
```
