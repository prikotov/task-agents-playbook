# Deploy (Развёртывание)

Общий обзор процесса развёртывания проекта TasK на различных окружениях.

## Окружения

### Development
Локальное окружение для разработки. Запускается через Docker/Podman Compose.

- [Настройка development окружения](development/setup-development/)

### Production
Продуктивное окружение для работы приложения.

- [Настройка production окружения](production/setup-production/)
- [Настройка worker-production](production/setup-worker-production/)
- [Развёртывание на production](production/deploy-production.md)

## Компоненты инфраструктуры

### База данных
- PostgreSQL с расширением pgvector для векторного поиска

### Очереди
- RabbitMQ — брокер очередей для асинхронных задач

### Хранилище
- MinIO — S3-совместимое объектное хранилище

### Кэширование
- Memcached — кэширующий сервер

### SSE
- Mercure — Server-Sent Events hub

## Процесс развёртывания

Подробное описание процесса развёртывания см. в [Git Workflow: Deploy](../git-workflow/deploy.md).

## См. также
- [Production: Deploy](production/deploy-production.md) — развёртывание на production
- [Git Workflow: Deploy](../git-workflow/deploy.md) — процесс деплоя через Git
