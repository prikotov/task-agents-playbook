# Production Infrastructure (MVP): Review and Recommendations

Документ фиксирует текущее **планируемое** устройство production-инфраструктуры TasK по материалам репозитория и
выделяет сильные стороны, риски и изменения подхода, которые стоит сделать до запуска MVP.

## Sources (repo)

Основные источники, на которых основан разбор:

- [`../devops/production/setup-production/*`](../devops/production/setup-production/) — целевая схема production-server, домены, SSL, DB, storage, secrets, deploy.
- [`../devops/production/setup-worker-production/*`](../devops/production/setup-worker-production/) — отдельный worker server, очереди, Supervisor/systemd, утилиты обработки.
- [`../devops/production/rabbitmq.md`](../devops/production/rabbitmq.md), [`../devops/production/mercure.md`](../devops/production/mercure.md), [`../devops/production/memcached.md`](../devops/production/memcached.md), [`../devops/production/storage/minio.md`](../devops/production/storage/minio.md).
- `compose.yaml` — dev-окружение, отражающее компоненты и связи.
- `devops/nginx/conf.d/dev/task.conf` — Nginx конфигурация для dev, декларируемо повторяющая production-схему.
- `.env`, `config/packages/messenger.yaml`, `config/packages/cache.yaml`, `config/packages/lock.yaml` — инфраструктурные DSN и runtime-зависимости.

## Current planned production topology (as-is)

### Entry points (domains)

Согласно [`../devops/production/setup-production/00-overview.md`](../devops/production/setup-production/00-overview.md):

- `ai-aid.pro` → 301 redirect на `task.ai-aid.pro`.
- `task.ai-aid.pro` → Symfony app `apps/web`.
- `api.ai-aid.pro` → Symfony app `apps/api`.
- `docs.ai-aid.pro` → Symfony app `apps/docs`.
- `blog.ai-aid.pro` → Symfony app `apps/blog`.

### Web node

Production-server (на текущем этапе — один server/VM, см. [`../devops/production/deploy-production.md`](../devops/production/deploy-production.md) и [`../devops/production/setup-production/*`](../devops/production/setup-production/)):

- **Nginx**: TLS termination (Certbot/Let’s Encrypt), host-based routing на разные `apps/*/public`, проксирование FastCGI в
  отдельный **PHP-FPM pool** пользователя `wwwtask` (unix socket). В dev-схеме это отражено в `devops/nginx/conf.d/dev/task.conf`.
- **Artifact files serving**: выдача артефактов через `X-Accel-Redirect` и `location /artifact-files/ { internal; alias ... }`
  (см. [`../devops/production/deploy-production.md`](../devops/production/deploy-production.md) и `devops/nginx/conf.d/dev/task.conf`).
- **PostgreSQL 17 + pgvector**: локально (по докам) и/или на выделенном host (по мере роста). Базовые шаги описаны в
  [`../devops/production/setup-production/07-database.md`](../devops/production/setup-production/07-database.md).
- **Mercure Hub**: отдельный сервис (systemd) на `127.0.0.1:3000`, проксируется Nginx на `/.well-known/mercure`
  (см. [`../devops/production/mercure.md`](../devops/production/mercure.md)).
- **Memcached**: рекомендуется как общий cache/rate limiting/lock store для web + worker (см. [`../devops/production/memcached.md`](../devops/production/memcached.md)),
  в runtime включён в `config/packages/cache.yaml`.

### Worker node

По [`../devops/production/setup-worker-production/README.md`](../devops/production/setup-worker-production/README.md) планируется (или уже зафиксирована как целевой вариант) отдельная
worker-машина без контейнеризации:

- Код проекта разворачивается в `/var/www/task` и запускается под пользователем `wwwtask`.
- Worker-процессы — это `messenger:consume <queue>` под Supervisor/systemd (см. [`../devops/production/setup-worker-production/messenger.md`](../devops/production/setup-worker-production/messenger.md)).
- Утилиты обработки (например, `whisper.cpp`, `yt-dlp`, `Docling`, `MinerU`, `Trafilatura`, `ffmpeg`) ставятся в `/opt/*` и
  доступны через env (`*_BIN_PATH`, `WHISPER_CPP_UTILS_PATH`, и т.д.).

### Shared / external services

- **RabbitMQ** (AMQP) как broker для Source pipeline (см. [`../devops/production/rabbitmq.md`](../devops/production/rabbitmq.md), `config/packages/messenger.yaml`).
  В production-доках рекомендуется устанавливать RabbitMQ в ОС и изолировать vhost/пользователей.
- **File storage**:
  - вариант `local`: `/mnt/task/files/*` (удобно, если web+workers на одной машине);
  - вариант `s3` через MinIO/S3: `STORAGE_DRIVER=s3` + `STORAGE_S3_*` (см. [`../devops/production/storage/minio.md`](../devops/production/storage/minio.md),
    [`../user/storage/index.md`](../user/storage/index.md), `config/packages/storage.php`).

## What is done well

- **Документация “как запускать prod” уже есть** и покрывает большинство critical components (Nginx/SSL, DB, secrets,
  RabbitMQ, Mercure, workers, утилиты обработки).
- **Осмысленное разделение ролей**: web/API и тяжёлые фоновые операции (Source pipeline) разведены через Symfony Messenger и RabbitMQ.
- **Изоляция очередей по стадиям** (несколько `source_*` transports + retry strategy) снижает blast radius и упрощает scaling.
- **Версионный guard для воркеров** (BuildVersionStamp/BuildVersionGuardMiddleware, описано в
  [`../devops/production/setup-worker-production/messenger.md`](../devops/production/setup-worker-production/messenger.md)) — правильная защита от “воркер крутится на старом коде”.
- **Схема выдачи артефактов через Nginx internal + X-Accel-Redirect** — хороший MVP-подход по производительности и security
  (нет прямого доступа к файлам, контроль через backend).
- **Symfony secrets** встроены в план production ([`../devops/production/setup-production/09-secrets.md`](../devops/production/setup-production/09-secrets.md)) — правильное направление.

## Critical issues / approach changes (MVP-aware)

Ниже — то, что может стать проблемой уже на MVP, с приоритетами.

### P0 (must before launch)

1. **Определиться с topology MVP: single host vs split nodes**
   - Сейчас одновременно описаны: (a) выдача артефактов из local FS через Nginx alias и (b) возможность вынести workers на
     отдельную машину + подключить S3/MinIO.
   - Это ключевая развилка: если workers не на web-host, то local `alias /mnt/task/files/...` перестаёт быть источником истины
     для UI, и нужно либо shared FS (NFS/Gluster), либо “artifact links” через S3 (signed URLs / proxy).

2. **Деплой как “git pull в рабочем каталоге” — риск даже для MVP**
   - Риск: частично обновлённые зависимости/кэш, отсутствие atomic switch и rollback, несогласованность web vs workers.
   - Минимальный upgrade подхода: релизная схема `releases/` + `current` symlink (как уже упоминается в checklist worker),
     плюс чёткий порядок: deploy web → миграции → warmup → restart/reload → deploy workers (или наоборот, если build-version guard).

3. **Backups/restore: DB есть, storage/broker/secrets — не закрыто end-to-end**
   - В [`../devops/production/setup-production/07-database.md`](../devops/production/setup-production/07-database.md) есть пример backup cron, но важно заранее отрепетировать restore.
   - Для `local` storage нужен plan backup/retention (объёмы быстро растут из-за артефактов).
   - Для S3/MinIO критично включить versioning/lifecycle и иметь процедуру восстановления доступа/кредов.
   - Для RabbitMQ стоит явно фиксировать: (a) backup definitions (users/vhosts/policies) и (b) стратегии на случай сбоя/переполнения диска.

4. **Security boundary для внутренних портов**
   - RabbitMQ management (`15672`), MinIO console (`9001`), DB (`5432`), Mercure (`3000`) должны быть доступны только из
     trusted network (localhost/VPN/ACL). Документация частично это упоминает, но для MVP лучше зафиксировать как “required”.

5. **Lock store и масштабирование**
   - В `.env` по умолчанию `LOCK_DSN=flock`. Для single host это приемлемо, но при split/нескольких worker nodes “локальные”
     lock’и не координируются. Если есть план на несколько нод — лучше заранее перейти на distributed lock store
     (например, PostgreSQL advisory locks) и задокументировать это как обязательное условие multi-node.

### P1 (should do soon after launch)

1. **Observability minimum**
   - Единый подход к логам (ротация, единые пути, сбор/централизация хотя бы для `prod.log` + worker logs).
   - Мониторинг очередей (backlog, consumers), disk usage для storage, и базовые health checks.
   - Error reporting (например, `SENTRY_DSN`) как обязательный компонент на production.

2. **Унификация production и dev конфигураций**
   - Сейчас dev использует Traefik, а prod — Nginx. Это нормально, но важно удерживать близость конфигов:
     “то же поведение роутинга/таймаутов/лимитов/mercure proxy”.
   - Рекомендация: закрепить “prod-like” Nginx конфиг как single source (уже частично сделано через `devops/nginx/conf.d/dev/task.conf`).

3. **Failure handling для Messenger**
   - Сейчас failure transport — Doctrine таблица (`failed_queue_messages`). Для MVP это ок, но нужно заранее определить:
     кто и как “разгребает” failed, какие алерты, и какой SLA по восстановлению.

### P2 (later / optional)

- Автоматизация provisioning (Ansible/Terraform) и/или переход на managed services (DB/S3/broker) для снижения ops-нагрузки.
- Формализация CI/CD (артефакты сборки, immutable deploy) вместо “сборка на сервере”.

## Architecture diagram

- Mermaid diagram (editable): `docs/architecture/production-infrastructure.mmd`
