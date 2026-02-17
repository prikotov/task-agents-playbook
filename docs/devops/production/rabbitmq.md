# RabbitMQ

## Установка (Centos/AlmaLinux)

На production-серверах RabbitMQ устанавливается напрямую в ОС, чтобы избежать лишних накладных расходов и упростить
обслуживание.

#### PHP AMQP extension (обязательное)

Symfony Messenger использует AMQP для соединения с RabbitMQ, поэтому на всех машинах с воркерами и CLI
должен быть установлен `ext-amqp`:

  ```bash
  sudo dnf install php-pecl-amqp
  ```

После установки убедитесь, что модуль загружен:

```bash
php -m | grep amqp
```

#### Установка RabbitMQ

> Полный и самый актуальный гид опубликован
> на [rabbitmq.com/docs/install-rpm](https://www.rabbitmq.com/docs/install-rpm). Ниже приведена адаптация под наши
> окружения; сверяйтесь с официальной документацией при обновлениях.

1. Подключите репозиторий Messaging SIG (RabbitMQ 3.8+/3.11+) — он добавляет актуальные пакеты.
   ```bash
   #sudo dnf install centos-release-rabbitmq-38
   #sudo dnf makecache
   ```

2. Установите Erlang и RabbitMQ:
   ```bash
   #sudo dnf install rabbitmq-server
   ```

3. Запустите и включите автозапуск службы:
   ```bash
   sudo systemctl enable --now rabbitmq-server
   sudo systemctl status rabbitmq-server
   ```

4. Включите management-плагин (по необходимости):
   ```bash
   sudo rabbitmq-plugins enable rabbitmq_management
   ```

5. Создайте рабочие vhost и пользователя:
   ```bash
   sudo rabbitmqctl add_vhost task
   sudo rabbitmqctl add_user task 'ChangeMe!'
   sudo rabbitmqctl set_permissions -p task task ".*" ".*" ".*"
   sudo rabbitmqctl set_user_tags task management
   ```
    - `add_vhost` создаёт изолированный namespace (очереди/обмены) для нашего приложения.
    - `add_user` добавляет отдельную учётную запись с паролем.
    - `set_permissions` даёт пользователю права на чтение/запись/конфигурацию внутри указанного vhost.

6. Установите rabbitmqadmin CLI-инструмент:
   ```bash
   # Скачайте утилиту с RabbitMQ сервера
   wget http://localhost:15672/cli/rabbitmqadmin
   
   # Сделайте файл исполняемым
   chmod +x rabbitmqadmin
   
   # Переместите в системную директорию (обычно /usr/sbin/ где находится rabbitmqctl)
   sudo mv rabbitmqadmin /usr/sbin/
   
   # Проверьте установку
   rabbitmqadmin --help
   ```
    - `rabbitmqadmin` не устанавливается автоматически вместе с RabbitMQ, его нужно скачать отдельно.
    - Инструмент использует HTTP API management-плагина для управления очередями.

7. Создайте очереди для воркеров:
   ```bash
   # Создание очередей
   rabbitmqadmin -u task -p 'ChangeMe!' -V task declare queue name=source_download durable=true
   rabbitmqadmin -u task -p 'ChangeMe!' -V task declare queue name=source_extract durable=true
   rabbitmqadmin -u task -p 'ChangeMe!' -V task declare queue name=source_convert durable=true
   rabbitmqadmin -u task -p 'ChangeMe!' -V task declare queue name=source_diarize durable=true
   rabbitmqadmin -u task -p 'ChangeMe!' -V task declare queue name=source_transcribe durable=true
   rabbitmqadmin -u task -p 'ChangeMe!' -V task declare queue name=source_make_document durable=true
   rabbitmqadmin -u task -p 'ChangeMe!' -V task declare queue name=source_make_document_chunks durable=true
   rabbitmqadmin -u task -p 'ChangeMe!' -V task declare queue name=source_events durable=true
   ```
    - `declare queue` создаёт очередь с указанным именем в vhost `task`.
    - `durable=true` обеспечивает сохранение очереди при перезапуске RabbitMQ.
    - Очереди используются воркерами TasK для обработки различных типов источников.

#### Firewall (firewalld)

```bash
sudo firewall-cmd --add-port=5672/tcp --permanent
sudo firewall-cmd --add-port=15672/tcp --permanent  # если нужен UI
sudo firewall-cmd --reload
```

Проверить, что порты добавлены в правила firewalld:

```bash
# просмотр всех открытых портов в active-зоне
sudo firewall-cmd --list-ports

# при использовании нескольких зон — вывести настройки нужной зоны
sudo firewall-cmd --zone=public --list-ports
```

Также можно убедиться, что порты реально слушаются локально:

```bash
sudo ss -tulpn | grep -E ':5672|:15672'
```

#### Конфигурация файлов

- Основной файл: `/etc/rabbitmq/rabbitmq.conf` (переменные окружения, TLS, лимиты).
- Дополнительные настройки: `/etc/rabbitmq/advanced.config`.

После правок:

```bash
sudo systemctl restart rabbitmq-server
```

#### Резервное копирование и мониторинг

- Бэкап каталога данных `/var/lib/rabbitmq`.
- Поднимите Prometheus exporter или интегрируйте `rabbitmq-diagnostics` в health-check.

#### Hardened production

- Доступ к `15672/tcp` только через VPN/ACL или reverse proxy с Basic Auth + HTTPS.
- Разделяйте пользователей/vhost для разных окружений, sandbox и CI.
- Включите TLS (`listeners.ssl.default`, `ssl_options` в `rabbitmq.conf`) при работе через публичные сети.
- Подключите централизованное логирование (`/var/log/rabbitmq/`) и алерты по метрикам очередей.

## Конфигурация

Все параметры подключения задаются через `.env` (см. блок `###> rabbitmq ###`):

- `RABBITMQ_HOST` — имя хоста, к которому подключаются сервисы Symfony.
- `RABBITMQ_PORT` — порт AMQP (по умолчанию `5672`).
- `RABBITMQ_VHOST` — virtual host, внутри которого создаются очереди проекта (по умолчанию `task`).
- `RABBITMQ_USER` и `RABBITMQ_PASSWORD` — сервисный пользователь для всех очередей.
- `RABBITMQ_MANAGEMENT_PORT` и `RABBITMQ_MANAGEMENT_URL` — HTTP-интерфейс плагина управления.
- `RABBITMQ_DSN` — готовая строка подключения (`amqp://...`) для Symfony Messenger и фоновых воркеров.
- `RABBITMQ_COMPOSE_PROJECT` — имя проекта для локального окружения, в котором крутится dev RabbitMQ (значение задаётся
  в `.env.dev`, по умолчанию `task`, чтобы все песочницы ходили к одному брокеру).
- `RABBITMQ_MANAGEMENT_USER`/`RABBITMQ_MANAGEMENT_PASSWORD` — при необходимости задайте отдельные креды для
  `rabbitmqadmin`; если переменные не определены, используются значения `RABBITMQ_USER`/`RABBITMQ_PASSWORD`, а скрипты
  автоматически выдают пользователю тег `administrator` для доступа к HTTP API.

Значения можно переопределить в `.env.local`/секретах. Перед публикацией в production задайте уникальные креды и
сохраните их в менеджере секретов.

### Общий брокер для sandbox/окружений

Один RabbitMQ может обслуживать несколько окружений (sandbox, dev, staging), если для каждого создать собственный
virtual host и пользователя:

1. На сервере RabbitMQ выполните:
   ```bash
   rabbitmqctl add_vhost sandbox_mycase
   rabbitmqctl add_user sandbox_mycase 'S3cret!'
   rabbitmqctl set_permissions -p sandbox_mycase sandbox_mycase ".*" ".*" ".*"
   ```
2. В `.env.local` нужной песочницы укажите параметры общего брокера:
   ```dotenv
   RABBITMQ_HOST=broker.internal
   RABBITMQ_PORT=5672
   RABBITMQ_MANAGEMENT_URL=https://rabbitmq.internal:15671
   RABBITMQ_VHOST=sandbox_mycase
   RABBITMQ_USER=sandbox_mycase
   RABBITMQ_PASSWORD=S3cret!
   RABBITMQ_DSN="amqp://${RABBITMQ_USER}:${RABBITMQ_PASSWORD}@${RABBITMQ_HOST}:${RABBITMQ_PORT}/${RABBITMQ_VHOST}"
   ```
3. При необходимости создайте отдельные роли только для чтения/наблюдения через `rabbitmqctl set_user_tags`.

Такой подход позволяет запускать любое количество песочниц без конфликта портов — каждая очередь физически находится в
своём vhost.

## Инспекция очередей

### Список очередей

Быстро посмотреть все очереди и ключевые счётчики можно через `bin/rabbitmq-queues` (или `make rabbitmq-queues`).
Команда выполняет `rabbitmqctl list_queues -p <vhost> name messages_ready messages_unacknowledged consumers state` на
сервере RabbitMQ и выводит таблицу.

### Статистика конкретной очереди

Для детального JSON по конкретной очереди используйте `bin/rabbitmq-queue-stats <queue>` или
`make rabbitmq-queue-stats QUEUE=<queue>`. Скрипт запрашивает `rabbitmqadmin list queues --format=raw_json` и через `jq`
оставляет только нужную очередь, показывая поля `messages`, `messages_ready`, `messages_unacknowledged`,
`message_bytes`, `consumers`, `state` и `arguments`.

## Management и мониторинг

- UI доступен на `http://localhost:15672` (dev) либо по адресу из `RABBITMQ_MANAGEMENT_URL` для серверного инстанса.
  Используйте выделенные креды, а для production оборачивайте интерфейс в HTTPS/Basic Auth или ограничивайте доступ ACL.
- В интерфейсе можно наблюдать очереди (`Queues`), соединения и сообщения, а также скачивать диаграммы
  производительности.
- Для алертов интегрируйте `/api/healthchecks/node` или `/api/metrics` c RabbitMQ management в существующий мониторинг (
  Prometheus, Zabbix и т.п.) и отслеживайте:
    - статус службы `rabbitmq-server` через `systemctl status` и метрики health-checkа;
    - число сообщений в failure-очереди;
    - наличие подключённых воркеров (`Channels`).

При планировании production убедитесь, что management-порт закрыт внешним ACL/VPN или ограничен reverse proxy.
