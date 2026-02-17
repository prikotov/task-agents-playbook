# Наблюдение за воркерами (логи, очереди, процессы)

Документ для повседневной отладки воркеров: где смотреть логи, как зайти в контейнер, как понять состояние очередей.

## Логи на локальной файловой системе

### Логи воркеров (dev, worker-cli)

Файлы пишутся в локальную директорию проекта:

- `var/containers/dev/worker-cli/log/worker-cli/source_download.log`
- `var/containers/dev/worker-cli/log/worker-cli/source_extract.log`
- `var/containers/dev/worker-cli/log/worker-cli/source_convert.log`
- `var/containers/dev/worker-cli/log/worker-cli/source_diarize.log`
- `var/containers/dev/worker-cli/log/worker-cli/source_transcribe.log`
- `var/containers/dev/worker-cli/log/worker-cli/source_make_document.log`
- `var/containers/dev/worker-cli/log/worker-cli/source_make_document_chunks.log`
- `var/containers/dev/worker-cli/log/worker-cli/source_events.log`
- ошибки: `var/containers/dev/worker-cli/log/worker-cli/*.error.log`
- лог supervisord: `var/containers/dev/worker-cli/log/worker-cli/supervisord.log`

Примеры:

```bash
# tail одного воркера
tail -f var/containers/dev/worker-cli/log/worker-cli/source_diarize.log

# ошибки только по diarize
tail -f var/containers/dev/worker-cli/log/worker-cli/source_diarize.error.log
```

### Логи приложения

Логи приложения уже проброшены в `var/log` проекта:

- `var/containers/dev/php-fpm/log/dev.log` (dev)
- `var/containers/dev/php-fpm/log/prod.log` (prod)

## Логи контейнера worker-cli (stdout/stderr)

Podman:

```bash
podman compose logs worker-cli
podman compose logs -f worker-cli
```

Docker:

```bash
docker compose logs worker-cli
docker compose logs -f worker-cli
```

Make (использует podman compose):

```bash
make worker-cli-logs
make worker-cli-logs-follow
```

## Вход в контейнер и процессы

Podman:

```bash
podman compose exec worker-cli bash
```

Docker:

```bash
docker compose exec worker-cli bash
```

Внутри контейнера доступны:

```bash
ps aux
supervisorctl status
mc
```

`supervisorctl status` показывает реальные воркеры (source_*), `ps aux` — все процессы.

## Очереди и delay-* retry

### Список и статистика

```bash
make rabbitmq-queues
make rabbitmq-queue-stats QUEUE=source_diarize
```

`delay_*` очереди — это очередь повторной попытки (retry). Сообщение уже было обработано с ошибкой и будет
перезапущено после задержки. Для понимания причины смотрите:

- логи конкретного воркера в `var/containers/dev/worker-cli/log/worker-cli/*.log` и `*.error.log`;
- failure transport: `bin/console messenger:failed:show`.

## Быстрый чек-лист

1. Проверить статус воркеров: `supervisorctl status`.
2. Открыть лог конкретного воркера: `tail -f var/containers/dev/worker-cli/log/worker-cli/<queue>.log`.
3. Проверить очередь: `make rabbitmq-queue-stats QUEUE=<queue>`.
4. Если сообщения попали в failure transport: `bin/console messenger:failed:show`.
