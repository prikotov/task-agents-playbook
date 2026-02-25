# DevOps

Раздел DevOps содержит документацию по инфраструктуре, развёртыванию и эксплуатации проекта TasK.

## Структура раздела

### Development
- [Настройка development окружения](development/setup-development/) — инструкции по настройке локального окружения для разработки

### Sandbox
- [Песочницы (Sandbox)](sandbox.md) — техническая документация по созданию, запуску и изоляции sandbox-окружений

### Production
- [Настройка production окружения](production/setup-production/) — инструкции по настройке production окружения
- [Настройка worker-production](production/setup-worker-production/) — инструкции по настройке worker для production
- [MinIO](production/storage/minio.md) — настройка S3-совместимого хранилища
- [Memcached](production/memcached.md) — настройка кэширующего сервера
- [Mercure](production/mercure.md) — настройка SSE hub
- [RabbitMQ](production/rabbitmq.md) — настройка брокера очередей
- [Настройка пользователя wwwtask](production/wwwtask-user-setup.md) — инструкции по настройке системного пользователя
- [Тестирование build-version](production/build-version-testing.md) — проверка версии сборки
- [Развёртывание на production](production/deploy-production.md) — процесс развёртывания
- [Индекс production](production/index.md) — индексный файл production раздела

### Systemd
- [Конфигурация Supervisor](systemd/) — конфигурационные файлы для управления процессами

## Развёртывание
- [Общее описание развёртывания](deploy.md) — обзор процесса развёртывания

## См. также
- [Git Workflow: Deploy](../git-workflow/deploy.md) — процесс деплоя через Git
