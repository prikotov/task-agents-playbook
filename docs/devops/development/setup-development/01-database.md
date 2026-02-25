# 01. Инфраструктура и база данных

## Предварительные требования

1. **Podman 4+** с поддержкой `podman compose`.
2. PHP 8.3+, Composer, Node.js (см. `README.md`).
3. Настроенный `.env.dev.local`. Минимальный пример:

   ```dotenv
   POSTGRES_USER=task
   POSTGRES_PASSWORD=task
   POSTGRES_DB=task_dev_db
   POSTGRES_HOST=127.0.0.1
   POSTGRES_PORT=5432
   POSTGRES_VERSION=17
   DATABASE_URL="postgresql://${POSTGRES_USER}:${POSTGRES_PASSWORD}@${POSTGRES_HOST}:${POSTGRES_PORT}/${POSTGRES_DB}?serverVersion=${POSTGRES_VERSION}&charset=utf8"
   ```

## Запуск PostgreSQL

```bash
podman compose --profile dev --env-file .env.dev.local up -d
```

Профиль `dev` активирует инфраструктурные сервисы (`database`, `mercure`, `rabbitmq`, mailer, `traefik`) и само приложение (`php-fpm`, `nginx`). Перед запуском Traefik убедись, что переменная `PODMAN_SOCK` указывает на сокет Podman (по умолчанию используется `/run/user/1000/podman/podman.sock`) и каталог `../certs` содержит нужные dev-сертификаты. Порт `5432` проброшен в `compose.yaml`. При необходимости можно запустить только часть сервисов:

```bash
podman compose --env-file .env.dev.local up -d database
```

## Расширение pgvector

Таблица `document_chunk_vector` использует тип `vector(1024)`, поэтому сервис `database` в `compose.yaml` сразу развёрнут на образе [pgvector/pgvector](https://hub.docker.com/r/pgvector/pgvector) (`pg${POSTGRES_VERSION:-17}`). Дополнительно при первом создании тома запускается скрипт `devops/postgres/init/001-pgvector.sql` с командой:

```sql
CREATE EXTENSION IF NOT EXISTS vector;
```

Если ты поднимаешь собственный экземпляр PostgreSQL (вне нашего compose) или пересобираешь контейнер без очистки томов, то самостоятельно запусти:

```bash
podman compose --env-file .env.dev.local exec -T database \
    psql -U "${POSTGRES_USER}" "${POSTGRES_DB}" \
    -c "CREATE EXTENSION IF NOT EXISTS vector"
```

Проверка: в `psql` команда `\dx vector` должна показывать установленное расширение. Без pgvector миграции и импорт дампов с `document_chunk_vector` завершатся ошибкой.

## Импорт дампа

Для `dev` окружения используйте восстановление через контейнер `database`:

```bash
make db-restore-dev DUMP=tmp/backups/task-dev.sql.gz
```

Команда перед импортом очищает `public` schema (`DROP SCHEMA ... CASCADE; CREATE SCHEMA public;`), чтобы восстановление было
воспроизводимым даже после предыдущих миграций.

Для дампов, снятых с `--clean`, в начале вывода `psql` возможны сообщения вида `ERROR: ... does not exist` на `DROP ...` операциях —
это ожидаемо при восстановлении в пустую схему.

После импорта примените миграции:

```bash
make migrate
```

Или одной командой:

```bash
make db-restore-and-migrate-dev DUMP=tmp/backups/task-dev.sql.gz
```

Почему так: в `.env.dev` по умолчанию `POSTGRES_HOST=database` (внутреннее имя compose-сети), поэтому запуск `bin/db-restore` с хоста
может завершиться ошибкой `could not translate host name "database"`.

Если у вас настроено хостовое подключение к Postgres (`POSTGRES_HOST=127.0.0.1`), можно использовать `bin/db-restore`:

```bash
bin/db-restore tmp/test.sql.gz
```

`bin/db-restore`:

- подтягивает параметры подключения из `.env`;
- распаковывает `.sql.gz` во временный файл `tmp/db-restore-*.sql` (добавь `--keep-temp`, чтобы оставить файл);
- запускает `psql --file=<dump.sql>`;
- удаляет временный SQL после успешного импорта.

Экспорт выполняется `bin/db-dump` (см. `bin/db-dump --help`).

## Проверка подключения

```bash
podman compose --env-file .env.dev.local exec -T database \
    psql -U "${POSTGRES_USER}" "${POSTGRES_DB}" -c "\dt"
```

Появившийся список таблиц означает, что БД готова.

## Остановка и очистка

```bash
podman compose --env-file .env.dev.local down            # остановка
podman compose --env-file .env.dev.local down --volumes  # удалить БД и тома
```

> `--volumes` уничтожает `task_database_data`, поэтому применяй только если готов переимпортировать дамп.

Перед коммитом убедись, что приложение использует новое подключение (см. `.env.dev.local`) и прогоняет `make check`.
