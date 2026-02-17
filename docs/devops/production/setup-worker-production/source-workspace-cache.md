# Source workspace cache (local) и prune

TasK использует `FileWorkspaceServiceInterface`, чтобы предоставлять локальные пути к файлам и workspace-директории для шагов пайплайна Source.
Для remote storage (S3/MinIO) можно включить persistent local cache, чтобы не перекачивать повторно один и тот же main file и workspace artifacts между стадиями workflow (например, diarization → transcription).

## Режим работы

- `SOURCE_FILE_WORKSPACE_MODE=cache` (по умолчанию): persistent cache, без per-call cleanup.
- `SOURCE_FILE_WORKSPACE_MODE=temp`: legacy поведение (tmp dir на каждый вызов + cleanup).

## Каталог кэша

- Default (см. `.env`): `SOURCE_FILE_WORKSPACE_CACHE_DIR=var/cache/source-workspace` (relative to project dir)
- Production recommendation: `SOURCE_FILE_WORKSPACE_CACHE_DIR=/var/cache/task/source-workspace`

## Cache layout

Каждый cache entry зеркалирует `storageKey` и хранит служебные файлы в `.meta`.
Миграция старого layout не выполняется — старые entry считаются устаревшими и могут быть удалены вручную или через prune.

```
cacheRoot/<storageKey>/
├── ...
└── .meta/
    ├── lock
    ├── last_access
    └── <file-hash>.json
```

`file-hash` = `sha256(key)`, где `key` — имя файла в storage (main file или artifact key).

## Политика очистки (TTL + max size — что наступит раньше)

Значения по умолчанию:
- `SOURCE_FILE_WORKSPACE_CACHE_TTL_SECONDS=604800` (7 дней)
- `SOURCE_FILE_WORKSPACE_CACHE_MAX_BYTES=53687091200` (50 GiB)

Команда очистки:

```bash
php bin/console app:source:workspace-cache:prune --env=prod
```

Опции:
- `--ttl=<seconds>` override TTL (0 отключает TTL pruning)
- `--max-bytes=<bytes>` override max size (0 отключает size pruning)
- `--dry-run` показать действия без удаления

## Настройка запуска в production

Проще всего настроить регулярный запуск через cron (рекомендуется). Пример (ежедневно в 03:30):

```cron
30 3 * * * wwwtask cd /var/www/task && /usr/bin/php bin/console app:source:workspace-cache:prune --env=prod --no-interaction
```

Примечание: prune пропускает entry, которые сейчас заблокированы запущенными джобами.
