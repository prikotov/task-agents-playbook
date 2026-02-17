# Mercure Hub: первый деплой и сопровождение

Mercure используется как отдельный хаб публикации/подписки для уведомлений (SSE) между backend (`MercureNotificationBroadcaster`) и фронтом (`NotificationStreamConfigProvider`). Сам хаб не входит в PHP-приложение, поэтому его нужно подготовить и поддерживать как самостоятельный сервис.

## 1. First Deploy Checklist

1. **Сгенерируйте ключи** (должны совпадать во всех окружениях):
   ```bash
   openssl rand -base64 32  # MERCURE_PUBLISHER_JWT_KEY
   openssl rand -base64 32  # MERCURE_SUBSCRIBER_JWT_KEY
   ```
   Один из ключей используйте как `MERCURE_JWT_SECRET`.
2. **Укажите переменные окружения** (пример для `.env.local` / `.env.prod.local`):
   ```
   MERCURE_URL=http://127.0.0.1:3000/.well-known/mercure    # адрес, куда ходит backend
   MERCURE_PUBLIC_URL=https://task.ai-aid.pro/.well-known/mercure  # адрес для браузера
   MERCURE_WORKER_PUBLISH_URL=http://mercure-internal/.well-known/mercure # private publish URL для worker-cli
   MERCURE_PUBLISHER_JWT_KEY=...
   MERCURE_SUBSCRIBER_JWT_KEY=...
   MERCURE_JWT_SECRET=...
   MERCURE_WORKER_PUBLISHER_JWT_SECRET=... # отдельный publisher secret для worker-cli (может отличаться от MERCURE_JWT_SECRET)
   MERCURE_EXTRA_DIRECTIVES_CORS_ORIGINS="cors_origins https://task.ai-aid.pro https://{additional domains}"
   NOTIFICATION_TOPIC_BASE_URI="https://task.ai-aid.pro/notifications/users"
   SOURCE_STATUS_TOPIC_BASE_URI="https://task.ai-aid.pro/source-status/users"
   ```
3. **Откройте порт** 3000/80 внутри сервера или docker-сети. Наружу порт не публикуется; доступ даётся через Nginx (`/.well-known/mercure`).
4. **Выберите режим запуска** (см. следующие разделы) и примените конфигурацию.
5. **Проверьте**:
   - `curl -i http://127.0.0.1:3000/.well-known/mercure` → `400 Missing "topic" parameter`
   - Из браузера `https://task.ai-aid.pro/.well-known/mercure?topic=...` получает `200` (в DevTools запрос `EventSource` остаётся в состоянии *pending*).

## 2. Продакшн (systemd + Nginx)

### 2.1 Установка
> Официальная документация: <https://mercure.rocks/docs/hub/install>. Всегда сверяйте актуальную версию и формат архивов на GitHub Releases.

1. Создайте системного пользователя:
   ```bash
   sudo useradd --system --no-create-home --shell /usr/sbin/nologin mercure
   ```
2. Скачайте подходящий архив.
   - Узнайте последнюю версию:
     ```bash
     MERCURE_VERSION=$(curl -s https://api.github.com/repos/dunglas/mercure/releases \
       | jq -r '.[] | select(.tag_name | test("^v")) | .tag_name' | head -n1)
     echo "Using ${MERCURE_VERSION}"
     ```
   - Для Linux x86_64 используйте:
     ```bash
     curl -L "https://github.com/dunglas/mercure/releases/download/${MERCURE_VERSION}/mercure_Linux_x86_64.tar.gz" -o /tmp/mercure.tgz
     ```
     Для других архитектур выберите архив `mercure_<OS>_<arch>.tar.gz` из списка релиза или установите пакет (`.deb`, `.rpm`, `.apk`).
   - Распакуйте бинарь и задайте владельца:
     ```bash
     sudo tar -C /usr/local/bin -xzf /tmp/mercure.tgz mercure
     sudo chown mercure:mercure /usr/local/bin/mercure
     ```
     Альтернатива (Debian/Ubuntu): добавить официальный репозиторий `deb [trusted=yes] https://repo.mercure.rocks/apt stable main` и поставить пакет через `apt install mercure`.
   - Для CentOS/RHEL/AlmaLinux подключите YUM-репозиторий:
     ```bash
     sudo tee /etc/yum.repos.d/mercure.repo >/dev/null <<'EOF'
     [mercure]
     name=Mercure
     baseurl=https://repo.mercure.rocks/yum/
     enabled=1
     gpgcheck=0
     EOF
     sudo dnf install mercure
     ```
3. Создайте каталог конфигурации `/etc/mercure` и файл `mercure.yaml`:
   ```yaml
   addr: ":3000"
   public_url: "https://task.ai-aid.pro/.well-known/mercure"
   publisher_jwt_key: "!ChangeMe!"
   subscriber_jwt_key: "!ChangeMe!"
   allow_anonymous: false
   cors_origins:
     - https://task.ai-aid.pro
   subscriptions:
     - selectors:
         - topic
   log_level: info
   ```
   JWT-ключи подставьте из `.env`.
   Подробнее про конфигурацию см. в официальном гайде: <https://mercure.rocks/docs/hub/config>.

### 2.2 Systemd unit
`/etc/systemd/system/mercure.service`:
```ini
[Unit]
Description=Mercure Hub
After=network.target

[Service]
User=mercure
Group=mercure
EnvironmentFile=/etc/mercure/mercure.env
ExecStart=/usr/local/bin/mercure run -config /etc/mercure/mercure.yaml
Restart=always
RestartSec=5
LimitNOFILE=65536

[Install]
WantedBy=multi-user.target
```
`/etc/mercure/mercure.env` хранит переменные (`MERCURE_PUBLISHER_JWT_KEY=...` и т.д.), чтобы их не дублировать.

Команды:
```bash
sudo systemctl daemon-reload
sudo systemctl enable --now mercure
sudo systemctl status mercure
```

### 2.3 Nginx
Добавьте блок до общих regexp (уже используется в `web-task.conf`):
```nginx
location ^~ /.well-known/mercure {
    proxy_pass http://127.0.0.1:3000/.well-known/mercure;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-For $remote_addr;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_buffering off;  # важно для SSE
}
```
Перезагрузите `nginx` и протестируйте `curl https://task.ai-aid.pro/.well-known/mercure`.

### 2.4 Поддержка
- **Health check**: `curl -f http://127.0.0.1:3000/healthz`.
- **Логи**: `journalctl -u mercure -f`.
- **Ротация ключей**: генерируйте новые JWT, обновляйте `/etc/mercure/mercure.env` и `.env*`, затем `systemctl restart mercure`.
  - Для worker publish path поддерживайте dual-key окно:
    1. добавить новый `MERCURE_WORKER_PUBLISHER_JWT_SECRET` на worker;
    2. разрешить новый key на Mercure;
    3. перезапустить worker;
    4. удалить старый key после стабилизации.
- **TLS**: завершается на Nginx; Mercure может слушать только HTTP.

## 5. Worker publish security baseline (source-status)

Для `worker-cli -> Mercure` соблюдайте минимальный baseline:

1. Используйте отдельный private publish endpoint (`MERCURE_WORKER_PUBLISH_URL`) без публичного ingress.
2. JWT claims для worker ограничивайте publish scope только на каноничный префикс source-status:
   - `${SOURCE_STATUS_TOPIC_BASE_URI}/*`
3. Не выдавайте worker subscribe claims.
4. Для HTTPS publish endpoint включайте строгую TLS verification:
   - доверенный internal CA в trust store worker-нод;
   - self-signed сертификаты допускаются только при явной установке private CA и включённой верификации цепочки.
5. Ошибки авторизации (`401`/`403`) и ошибки хаба логируйте раздельно для triage delivery pipeline.

## 3. Разработка (Docker Compose)

В репозитории уже есть сервис `mercure` (`compose.yaml:22-47`) и отдельный Podman-стек `devops/mercure/docker-compose.podman.yml`. Для быcтрого запуска добавлены CLI-скрипты (лежит в `bin/`):

| Команда              | Назначение                                                                                                    |
|----------------------|----------------------------------------------------------------------------------------------------------------|
| `bin/mercure-up`     | Поднять контейнер через `podman-compose`, автоматически загрузив `.env.dev` + `.env.dev.local`.               |
| `bin/mercure-down`   | Остановить стек (`podman-compose down`).                                                                       |
| `bin/mercure-check`  | Показать `podman-compose ps` и выполнить `curl` к `/healthz` и `/.well-known/mercure`.                         |
| `bin/mercure-logs`   | Показывать live-логи контейнера (`podman-compose logs -f mercure`).                                           |

### 3.1 Переменные и ключи
1. Сгенерируйте JWT ключи (см. чек-лист в разделе 1) и пропишите их в `.env.dev.local`:
   ```dotenv
   MERCURE_PUBLISHER_JWT_KEY="base64..."
   MERCURE_SUBSCRIBER_JWT_KEY="base64..."
   MERCURE_URL=http://127.0.0.1:3000/.well-known/mercure
   MERCURE_PUBLIC_URL=http://127.0.0.1:3000/.well-known/mercure
   MERCURE_EXTRA_DIRECTIVES_CORS_ORIGINS="cors_origins http://127.0.0.1:8000 http://localhost:8000"
   ```
   Скрипт `mercure-up` проверяет, что оба ключа заданы; при отсутствии выдаёт ошибку и не поднимает контейнер.
2. При необходимости можно передать дополнительные dotenv-файлы для `mercure-up`:
   ```bash
   bin/mercure-up --env-file /path/to/extra.env
   ```
   Для кастомного compose-файла есть флаги `--dir`/`--compose`.

### 3.2 Запуск и проверка
1. Запустите:
   ```bash
   bin/mercure-up                # или make -f Makefile.sandbox mercure-up
   # альтернатива (docker compose):
   docker compose \
     --env-file .env.dev \
     --env-file .env.dev.local \
     up -d mercure
   ```
   Если используете Podman напрямую, как и раньше можно задать `DOCKER_CONFIG=/tmp/docker-empty` или собственный `podman-credential`.
2. Проверка:
   ```bash
   bin/mercure-check             # под капотом: podman-compose ps + curl
   curl -i http://127.0.0.1:3000/.well-known/mercure
   ```
   Для live-логов используйте `bin/mercure-logs`.
3. Остановка:
   ```bash
   bin/mercure-down              # или make -f Makefile.sandbox mercure-down
   # альтернатива: docker compose down
   ```

## 4. Сопровождение и отладка

- **Диагностика 4xx/5xx**: если в браузере `403`/`502`, проверьте, что Nginx-прокси имеет приоритет (`^~`), а хаб запущен. `502` → сервис не ответил, `403` → блокирует Nginx/ACL, `401` → неверный JWT.
- **Мониторинг**: подключите `systemd`-metrics или внешний Prometheus через `/metrics`.
- **Обновление версии**:
  1. Скачайте новый бинарь или выполните `docker compose pull mercure`.
  2. Перезапустите службу/контейнер, убедитесь, что health check зелёный.
- **Автотесты**: локально достаточно выполнить `curl` + открыть UI и убедиться, что запрос `EventSource` остаётся в состоянии pending без ошибок.

Такое разделение позволяет повторяемо поднимать Mercure как на продакшне (systemd), так и в dev (Docker Compose). При первом деплое придерживайтесь чек-листа, а при сопровождении используйте health-check и логи для быстрого обнаружения проблем.
