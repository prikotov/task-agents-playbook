# Дамп базы данных

Утилита `bin/db-dump` снимает plain SQL дамп PostgreSQL с автоматическим сжатием `gzip`. Скрипт читает параметры
подключения из `.env`, `.env.local`, `.env.dev.local` (приоритет задаётся Symfony Dotenv) и дополнительно поддерживает
ручной выбор директории/имени файла.

## Подготовка

1. Установите клиентские утилиты PostgreSQL (`pg_dump`) и `gzip`. На Linux они входят в пакет `postgresql-client` /
   `postgresqlXX-client`.
2. Заполните `.env.*` переменные `DATABASE_URL` **или** набор `POSTGRES_USER|PASSWORD|HOST|PORT|DB`.

## Снятие дампа

```bash
# из корня репозитория
bin/db-dump \
  --output-dir tmp/backups \
  --filename task-dev.sql.gz
```

- `--output-dir`, `-o` — куда сложить дампы (по умолчанию `./tmp`).
- `--filename`, `-f` — имя файла (расширение `.sql.gz` добавляется автоматически).

В каталоге появится два файла: временный `.sql` и итоговый архив `.sql.gz`. После удачного завершения остаётся только
архив, пригодный для переноса в песочницы или другие инстансы.

### Альтернатива через контейнер database

Если локальный `pg_dump` несовместим с версией сервера, можно снять дамп напрямую из контейнера:

```bash
docker compose -p task exec -T database env PGPASSWORD=task \
  pg_dump --format=plain --encoding=UTF8 --no-owner --no-privileges --clean \
  -U task task_dev_db | gzip > tmp/backups/task-dev.sql.gz
```

## Восстановление в песочнице

```bash
gunzip -c tmp/backups/task-dev.sql.gz \
  | PGPASSWORD=sandbox_pass psql -h sandbox-pg -U sandbox_user sandbox_db
```

- `gunzip -c` распаковывает дамп на лету в stdout.
- `psql` принимает поток и восстанавливает схему/данные. Параметры подключения задаются переменными песочницы.

## Диагностика

Если скрипт завершился с ошибкой:

| Сообщение                                   | Решение                                                                |
|---------------------------------------------|------------------------------------------------------------------------|
| `Не найден pg_dump`                         | Установите PostgreSQL client tools и убедитесь, что `pg_dump` в `$PATH`.|
| `Не найден gzip`                            | Установите пакет `gzip`.                                               |
| `DATABASE_URL ... не заданы`                | Проверьте `.env.local`/`.env.dev.local` или передайте `POSTGRES_*`.    |
| `command timed out`                         | Проверьте доступность сервера БД/сетевого туннеля, повторите команду.  |

Логи `pg_dump` попадают в stdout/stderr скрипта; их можно перенаправить в файл при необходимости.
