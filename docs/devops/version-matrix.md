# Матрица версий ПО и идентичность окружений

Этот документ фиксирует фактические версии ПО на production-хостах.

## Источник данных

- Матрица формируется бинарём [`bin/version-matrix-snapshot`](../../bin/version-matrix-snapshot).
- Пример запуска: `bin/version-matrix-snapshot --aserv aserv --nserv nserv --format markdown`.

## Принцип идентичности (Environment Parity)
Мы стремимся к тому, чтобы окружение разработки было максимально приближено к продуктовому. Все изменения версий должны сначала тестироваться локально, затем на Staging и только после этого попадать в Production.

## Хосты production

- `aserv` — web server
- `nserv` (`hostname=neyrosha`) — workers server

## Системное ПО и ОС (Production Snapshot)

| Компонент | `aserv` (web) | `nserv` (workers) | Примечание |
| :--- | :--- | :--- | :--- |
| OS | CentOS Stream 9 | AlmaLinux 10.1 (Heliotrope Lion) | |
| Kernel | 5.14.0-669.el9.x86_64 | 6.12.0-124.31.1.el10_1.x86_64 | |
| PHP CLI | 8.4.17 | N/A (pkg not installed system-wide) | На `nserv` PHP используется внутри runtime/venv |
| PostgreSQL (client pkg) | postgresql17-17.7-1PGDG.rhel9.x86_64 | postgresql17-17.7-1PGDG.rhel10.x86_64 | |
| Nginx | nginx-1.20.1-26.el9.x86_64 | N/A | |
| RabbitMQ | rabbitmq-server-4.2.3-1.el8.noarch | N/A | `rabbitmqctl` на `aserv` требует root/rabbitmq |
| Redis/KeyDB | N/A | N/A | |
| Mercure/Caddy | N/A | N/A | |
| Container runtime | podman-5.6.0-2.el9.x86_64 | N/A | |

## Ключевые библиотеки (Backend)

| Библиотека | Версия (Constraint) | Файл |
| :--- | :--- | :--- |
| Symfony | 7.3.x | `composer.json` |
| Doctrine ORM | ^3.0 | `composer.json` |

## Ключевые библиотеки (Frontend)

| Библиотека | Версия (Constraint) | Файл |
| :--- | :--- | :--- |
| Bootstrap | 5.3.x | `importmap.php` / Phoenix theme |
| Stimulus | ^3.2 | `importmap.php` |
| Turbo | ^7.3 | `importmap.php` |

## Worker-CLI инструменты (Production, `nserv`)

| Утилита | Версия | Назначение |
| :--- | :--- | :--- |
| Python | 3.12.12 | Runtime для утилит |
| yt-dlp | 2026.02.04 | Загрузка/экстракция из web-source |
| ffmpeg | 7.1.2 | Аудио/видео конвертация |
| pdfinfo (poppler) | 24.02.0 | Извлечение метаданных PDF |
| whisper-cli | N/A | Не установлен в `$PATH` |

## Diarize stack (Production, `nserv`, `/opt/diarize`)

| Пакет | Версия |
| :--- | :--- |
| pyannote-audio | 4.0.3 |
| speechbrain | 1.0.3 |
| torch | 2.8.0 |
| torchaudio | 2.8.0 |
| huggingface_hub | 0.35.3 |
| soundfile | 0.13.1 |
| requests | 2.32.5 |
| CUDA available | true |
| torch CUDA runtime | 12.8 |

## Политика обновления версий

- Версии утилит и Python-стека в `worker-cli` должны быть зафиксированы (без `latest` и без плавающих constraints).
- Любое изменение версии (upgrade/downgrade) выполняется только через отдельную задачу в `todo/`.
- Для задачи на обновление версий обязательны прогоны проверок:
  - `make build-worker-cli`
  - `make build-worker-cli-e2e`
  - `make tests-e2e-source-pipeline`
- После успешных прогонов обновляется этот файл (`docs/devops/version-matrix.md`) с фактическими версиями.

---
*Последнее обновление: 2026-02-25 (снято через `ssh aserv` и `ssh nserv`)*
