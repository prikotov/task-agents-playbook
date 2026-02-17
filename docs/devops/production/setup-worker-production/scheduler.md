# Планировщик задач

В проекте используется [Symfony Scheduler](https://symfony.com/doc/current/scheduler.html) для запуска фоновых команд, помеченных атрибутами `#[AsTask]` и `#[AsCronTask]`. Планировщик обрабатывает все задачи через transport Messenger `scheduler_default`, поэтому для выполнения расписаний запускайте воркер `messenger:consume`.

## Продакшен

Запускайте воркер планировщика как отдельный сервис и обеспечьте его автоматический перезапуск. Базовая команда для сервера:

```bash
sudo -u wwwtask php /var/www/task/bin/console messenger:consume scheduler_default --env=prod --no-interaction --verbose
```

Рекомендуем оформить воркер как systemd unit (`/etc/systemd/system/task-scheduler.service`):

```ini
[Unit]
Description=TasK Symfony Scheduler
After=network.target

[Service]
Type=simple
User=wwwtask
WorkingDirectory=/var/www/task
ExecStart=/usr/bin/php /var/www/task/bin/console messenger:consume scheduler_default --env=prod --no-interaction --verbose
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

После добавления unit-файла выполните `sudo systemctl daemon-reload`, затем `sudo systemctl enable --now task-scheduler.service`.

## Локальная разработка

Для локальной разработки достаточно запустить воркер в отдельной вкладке терминала:

```bash
symfony console messenger:consume scheduler_default --env=dev --time-limit=600 --verbose
```

Опция `--time-limit=600` завершает воркер через 10 минут, чтобы он не оставался висеть после завершения работы.
