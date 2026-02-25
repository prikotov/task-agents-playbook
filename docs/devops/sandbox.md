# Sandbox в DevOps

Техническая документация по sandbox-окружениям для разработчиков и DevOps.

User-facing инструкция находится в [`docs/user/sandbox/sandbox.md`](../user/sandbox/sandbox.md).

## 1) Назначение

Sandbox — это отдельная копия репозитория в `~/MyProjects/TasK/Sandbox/<slug>` для изолированной разработки, экспериментов и проверки гипотез без влияния на основное Development-окружение.

Когда использовать sandbox:
- сложные и рискованные задачи;
- параллельная работа нескольких AI-агентов;
- проверка изменений в инфраструктуре/конфигурации.

## 2) Архитектура sandbox

Ключевые элементы:
- sandbox создаётся скриптом [`devops/scripts/sandbox-new.sh`](../../devops/scripts/sandbox-new.sh);
- compose-файл sandbox: `docker-compose.sandbox.yml` (генерируется из [`devops/sandbox/templates/docker-compose.sandbox.yml`](../../devops/sandbox/templates/docker-compose.sandbox.yml));
- sandbox контейнер подключается к общей внешней сети `${PROJECT}_net` (обычно `task_net`);
- маршрутизация выполняется через общий Traefik.

Структура каталогов:

```text
~/MyProjects/TasK/
├── Development/
└── Sandbox/
    ├── codex-cli/
    ├── task-123/
    └── ...
```

## 3) Создание sandbox

Запускать из `Development`:

```bash
bin/sandbox-new <slug> --project task [--init] [--shallow] [--no-cache-build] [--force-template]
```

Пример:

```bash
bin/sandbox-new task-integration-isolation --project task --init
```

Что делает скрипт:
- клонирует репозиторий в `../Sandbox/<slug>`;
- рендерит `docker-compose.sandbox.yml`, `Dockerfile.sandbox`, `Caddyfile.sandbox`;
- создаёт/обновляет `Sandbox/<slug>/.env.local` с переменными:
  - `PROJECT=<project>`;
  - `SANDBOX=<slug>`;
  - `COMPOSE_PROJECT_NAME=<project>-sandbox-<slug>-dev`;
  - `COMPOSE_TEST_PROJECT_NAME=<project>-sandbox-<slug>-test`;
- собирает и поднимает sandbox контейнер с compose project name `<project>-sandbox-<slug>`.

## 4) Конфигурация и маршрутизация

Основные переменные:
- `PROJECT` — базовое имя проекта (обычно `task`);
- `SANDBOX` — slug песочницы;
- `COMPOSE_PROJECT_NAME` — проект для dev-контейнеров sandbox;
- `COMPOSE_TEST_PROJECT_NAME` — проект для test-контейнеров sandbox.

Traefik:
- dashboard: `http://localhost:8081/dashboard/`;
- домены sandbox:  
  `https://<slug>.<project>.localhost:8443`  
  `https://api-<slug>.<project>.localhost:8443`  
  `https://blog-<slug>.<project>.localhost:8443`  
  `https://docs-<slug>.<project>.localhost:8443`.

## 5) Запуск и остановка

Для контейнеров приложения sandbox (файл `docker-compose.sandbox.yml`):

```bash
cd ~/MyProjects/TasK/Sandbox/<slug>
podman-compose -p task-sandbox-<slug> -f docker-compose.sandbox.yml up -d
podman-compose -p task-sandbox-<slug> -f docker-compose.sandbox.yml down --remove-orphans
```

Для обычного dev/test workflow внутри sandbox используйте `make`-команды репозитория:
- `make up`
- `make down`
- `make tests-integration`

## 6) Работа с кодом и тестами

Git в sandbox настраивается отдельно от `Development`:
- ветка создаётся и ведётся в sandbox-репозитории;
- remote/credentials используются sandbox-экземпляра.

Интеграционные тесты запускаются стандартно:

```bash
make tests-integration
```

Изоляция достигается за счёт:
- разных compose project names (`*-dev` и `*-test`);
- отдельного project name для каждой sandbox (`task-sandbox-<slug>-*`);
- использования внутренней сети compose (`database:5432`) без публикации порта БД на host.

Это позволяет запускать `make tests-integration` параллельно в `Development` и в sandbox без коллизий имён контейнеров и портов.

## 7) Изоляция и типовые коллизии

Что изолировано:
- имена контейнеров/volumes между Development и sandbox;
- test-контейнеры относительно dev-контейнеров.

Что остаётся общим:
- внешняя сеть `${PROJECT}_net`;
- Traefik;
- при общей конфигурации — внешние интеграции (например, RabbitMQ instance).

Риски:
- изменение конфигурации общей сети может затронуть все sandbox;
- одинаковые OAuth redirect URI между sandbox вызовут ошибки входа;
- общий broker без раздельных vhost приведёт к смешиванию очередей.

## 8) OAuth и внешние провайдеры

Настройка OAuth для sandbox описана в:
- [`docs/user/auth/oauth/index.md`](../user/auth/oauth/index.md)

Для каждой sandbox добавляйте отдельные redirect URI.

## 9) Дамп БД

Работа с дампами описана в:
- [`docs/user/db-dump.md`](../user/db-dump.md)

Рекомендуется использовать отдельные базы/схемы для sandbox, если нужны реалистичные данные.

## 10) Очереди и RabbitMQ

См.:
- [`docs/devops/production/rabbitmq.md`](production/rabbitmq.md)

Рекомендация:
- для каждой sandbox задавать отдельные `RABBITMQ_VHOST`/`RABBITMQ_USER` в её `.env.local`.

## 11) Очистка и удаление sandbox

Порядок удаления:

```bash
cd ~/MyProjects/TasK/Sandbox/<slug>
podman-compose -p task-sandbox-<slug> -f docker-compose.sandbox.yml down --remove-orphans
rm -rf ~/MyProjects/TasK/Sandbox/<slug>
```

Опционально проверьте, не остались ли ресурсы:

```bash
podman ps -a --filter "label=io.podman.compose.project=task-sandbox-<slug>"
podman volume ls
```

Удаление volumes/networks выполняйте только после проверки, что они не используются другими окружениями.
