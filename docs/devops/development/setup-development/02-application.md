# 02. Приложение (PHP-FPM + Nginx)

После того как инфраструктура (`database`, `mercure`, `rabbitmq`, `traefik`) поднята, можно запустить само приложение в контейнерах Podman/Docker. Конфигурация находится в `compose.yaml` и использует тот же `make up`, так что достаточно обновить окружение и выполнить команды ниже.

## Предварительные требования

1. Выполнены шаги из [01-database](01-database.md) — создан `.env.dev.local`, работают сервисы PostgreSQL, RabbitMQ, Traefik, Mercure.
2. Включён rootless Podman API: `systemctl --user enable --now podman.socket podman.service` (проверка: `curl --unix-socket $XDG_RUNTIME_DIR/podman/podman.sock http://d/_ping` → `OK`).  
   Экспортируй путь до сокета, чтобы `compose.yaml` автоматически смонтировал его внутрь Traefik: `export PODMAN_SOCK=$XDG_RUNTIME_DIR/podman/podman.sock`.
3. В каталоге `../certs/` (относительно репозитория) лежат dev-сертификаты (`localhost-*`, `task-wc-*`). Если их нет — сгенерируй: `bin/certs-init --project task` (идемпотентно, устанавливает root CA через `mkcert -install`). После генерации браузер может потребовать перезапуск, чтобы увидеть новый root CA.  
4. Добавь (или проверь наличие) записей `task.localhost`, `api.task.localhost`, `blog.task.localhost`, `docs.task.localhost` в `/etc/hosts`, либо используй wildcard `*.localhost` (современные ОС резолвят его в `127.0.0.1` автоматически).
5. Создай каталоги для bind-mounts: `mkdir -p var/containers/dev/php-fpm/{cache,log,uploads,tmp} var/containers/dev/worker-cli/{cache,log,uploads,tmp,cache-root} var/containers/dev/shared/storage/{source,documents,chunks,avatars}`.

## Запуск

```bash
# Разовая пересборка php-образа (после изменения Dockerfile)
make build-php

# Старт всех dev-сервисов + php-fpm + nginx
make up

# Проверка статуса
podman compose --env-file .env.dev.local ps

# Консоль внутри php-fpm контейнера
make app-shell
```

### PyTorch wheels (CPU/GPU)

`worker-cli` устанавливает зависимости, использующие PyTorch. По умолчанию используется индекс
pip по умолчанию. Если нужна предсказуемая установка CPU или CUDA сборок, задай
`PYTORCH_INDEX_URL` — он будет использоваться как основной индекс, а PyPI останется
дополнительным (для прочих пакетов).

```bash
export PYTORCH_INDEX_URL=https://download.pytorch.org/whl/cu121
make build-all
```

Для CPU можно явно указать `https://download.pytorch.org/whl/cpu` — это помогает избежать
скачивания CUDA-зависимостей на машинах без GPU.

Можно также зафиксировать `PYTORCH_INDEX_URL` в `.env.dev.local`, чтобы переменная подхватывалась
при каждом `make build-*`.

Для возврата к дефолту достаточно убрать переменную и пересобрать образ.

### Whisper модель (dev/e2e)

Модель `ggml-large-v3.bin` не включается в образ `worker-cli`. Она хранится в `var/containers/shared/whisper/models` и шарится между `worker-cli` в dev/e2e окружениях. Внутри контейнера она доступна в `/var/www/task/var/whisper/models` (и дополнительно примонтирована в `/opt/whisper.cpp/models-cache` для совместимости с whisper.cpp).
Для первичной загрузки:

```bash
make whisper-model
```

### HuggingFace кэш (dev/e2e)

Модели с HuggingFace (MinerU, Docling, pyannote-audio и др.) кэшируются в `var/containers/shared/huggingface` и шарятся между `worker-cli` в dev/e2e окружениях. Это позволяет избежать повторного скачивания моделей при каждом запуске тестов.

- Кэш автоматически создаётся при запуске контейнеров
- Не удаляется при `make e2e-clean-host`
- Игнорируется в git (`.gitignore`)

Переменная окружения `HF_HOME` внутри контейнеров указывает на `/var/www/task/var/huggingface`.

В контейнере по умолчанию смонтирован весь репозиторий (`/var/www/task`). После `make up` выполните:

```bash
make app-shell                          # откроет bash в контейнере php-fpm
composer install                        # устанавливаем зависимости
bin/console doctrine:migrations:migrate # опционально, если нужно заполнить БД
bin/console about                       # быстрая проверка окружения
```

### Доступ

- `https://task.localhost:8443` — Web UI (`apps/web`).
- `https://api.task.localhost:8443` — публичное API.
- `https://blog.task.localhost:8443` — приложение блога.
- `https://docs.task.localhost:8443` — документация.

Traefik автоматически перенаправляет HTTP (`:8080`) на HTTPS (`:8443`). Все руты обслуживаются контейнером `nginx`, который проксирует FastCGI-запросы в `php-fpm`, `/artifact-files/*` в shared storage (`/var/www/task/var/storage/source`, host: `var/containers/dev/shared/storage/source`) и `/artifact-cache/*` в Source workspace cache (`var/cache/source-workspace`). Для быстрой smoke-проверки можно выполнить `curl -vk --noproxy '*' --resolve task.localhost:8443:127.0.0.1 https://task.localhost:8443`.

### Остановка

```bash
make down          # остановка и очистка контейнеров профиля dev
make infra-down    # остановка без удаления томов
```

## TLS и Traefik: что делать, если браузер показывает ошибку сертификата

1. Сгенерируй dev-сертификаты и установи root CA:  
   `bin/certs-init --project task`
2. Перезапусти Traefik, чтобы он подхватил certs из `../certs`:  
   `podman compose --env-file .env.dev.local --profile dev restart traefik`
3. Отключи прокси для локальных доменов (иначе HTTPS будет идти через внешний прокси и рваться на TLS-handshake):  
   `export NO_PROXY=task.localhost,.localhost,127.0.0.1,localhost`  
   `unset HTTPS_PROXY HTTP_PROXY`  # или сделай пустыми в текущей сессии
4. Проверь сертификат и ALPN:  
   `NO_PROXY=task.localhost,.localhost,127.0.0.1,localhost HTTPS_PROXY= HTTP_PROXY= bin/traefik-check --host task.localhost --compose compose.yaml`
5. Для ручного smoke-теста без прокси:  
   `NO_PROXY=task.localhost,.localhost,127.0.0.1,localhost HTTPS_PROXY= HTTP_PROXY= curl -v --http2 https://task.localhost:8443/ -I`

Если Firefox/Chrome всё ещё жалуются, удалённо закрой браузер и запусти заново — `mkcert -install` добавляет CA в системное хранилище, но браузер может его не перечитать без рестарта.

## Подробности реализации

- `devops/docker/php/php-fpm.Dockerfile` описывает php-fpm 8.4 образ с необходимыми расширениями (`pdo_pgsql`, `intl`, `gd`, `zip`, `bcmath`, `pcntl`, `opcache`) и преднастроенным `php.ini` (`devops/docker/php/conf.d/task.ini`).
- `nginx` берёт конфиг из `devops/nginx/conf.d/dev/task.conf`, полностью повторяя продовую схему (`client_max_body_size`, таймауты, `/.well-known/mercure`, `X-Accel-Redirect` для `/artifact-files` и `/artifact-cache`) и проксирует FastCGI в сервис `php-fpm:9000`.
- Все сервисы подключены к одной compose-сети, поэтому в контейнере используются DNS-имена `database`, `rabbitmq`, `mercure`. Значения `POSTGRES_HOST`, `MERCURE_URL`, `MERCURE_PUBLIC_URL`, `NOTIFICATION_TOPIC_BASE_URI` переопределены на уровне сервиса `php-fpm`, чтобы Symfony автоматически подхватывал нужные URL. Для Traefik обязательно пробрасываем `$PODMAN_SOCK`, иначе docker-провайдер не увидит контейнеры и отдаст 404/502.
- Для быстрого доступа есть цель `make app-shell`, а логи можно смотреть через `podman compose --env-file .env.dev.local logs -f php-fpm` или `... logs -f nginx`.

Если нужно отключить Traefik и прокидывать порты напрямую, отредактируйте `compose.yaml` (или используйте механизм профилей/переменных) с публикацией портов `nginx` (`80/443`). По умолчанию вся маршрутизация идёт через Traefik, поэтому `nginx` не открывает порты наружу.
