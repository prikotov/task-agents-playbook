# Релиз на два сервера (Web + Workers)

# Все команды на сервере выполняются от пользователя wwwtask:
# cd /var/www/task && sudo -u wwwtask COMMAND

Порядок деплоя: **Web → Workers** (защита через BuildVersionGuardMiddleware).

## 1. Деплой на Web-сервер (ai-aid.pro)

```bash
ssh wwwtask@ai-aid.pro
sudo -u wwwtask -i

# Деплой
cd /var/www/task && sudo -u wwwtask git pull
cd /var/www/task && sudo -u wwwtask make install

# Миграции (если есть)
cd /var/www/task && sudo -u wwwtask php bin/console doctrine:migrations:migrate
```

## 2. Деплой на Worker-сервер

```bash
ssh wwwtask@worker-server
sudo -u wwwtask -i

# Деплой
cd /var/www/task && sudo -u wwwtask git pull
cd /var/www/task && sudo -u wwwtask make install

# Перезапуск воркеров
sudo supervisorctl stop task-workers:* && sudo supervisorctl start task-workers:*
sudo supervisorctl status
```

> **⚠️ Важно: добавление новых воркеров (новых Messenger transports)**
>
> При релизе новых воркеров (новых `*_TRANSPORT_DSN` в `config/packages/messenger.yaml`) необходимо добавить соответствующие переменные окружения в `.env.local` **на обоих серверах** (Web и Worker) **до** перезапуска воркеров.
>
> Для source status live updates дополнительно проверьте:
> - `SOURCE_STATUS_LIVE_UPDATES_TRANSPORT_DSN`,
> - `SOURCE_STATUS_LIVE_DEGRADATION_UI` (`off`/`limited`/`on`),
> - `SOURCE_STATUS_LIVE_UPDATES_MAX_STALENESS_SECONDS`.
>
> > **Подробная информация:** [Настройка Supervisor воркеров](setup-worker-production/supervisor-services.md)

> **⚠️ Важно: версионирование кода**
>
> При деплое используется механизм версионирования для защиты от несогласованности между Web и Worker серверами.
>
> - Любое изменение кода (включая документацию) создаёт новую версию
> - **Требуется одновременный деплой на оба сервера** (сначала Web, затем Workers)
> - Если версии не совпадут, воркеры остановятся с ошибкой `BuildVersionMismatchException`
> - Различие timestamps между серверами допустимо при совпадении git SHA
>
> Проверка версий после деплоя:
> ```bash
> # Проверить текущую версию на обоих серверах
> sudo -u wwwtask cat /var/www/task/var/build/version
>
> # Проверить git SHA (должен совпадать с первой частью версии)
> sudo -u wwwtask git rev-parse HEAD
> ```
>
> Подробная информация: [Версионирование кода](code-versioning.md)

## 3. Health-check

### Проверки на Web-сервере

```bash
# Проверка endpoints
curl -I https://task.ai-aid.pro/
curl -I https://api.ai-aid.pro/
curl -I https://blog.ai-aid.pro/
curl -I https://docs.ai-aid.pro/

# Проверка логов
sudo tail -20 /var/log/nginx/task-error.log
sudo tail -20 /var/www/task/var/log/prod.log
```

#### Проверка очередей

```bash
# Проверка статистики очередей
cd /var/www/task && sudo -u wwwtask php bin/console messenger:stats

# Показать failed messages
cd /var/www/task && sudo -u wwwtask php bin/console messenger:failed:show

# Перезапустить failed message по ID
cd /var/www/task && sudo -u wwwtask php bin/console messenger:failed:retry {id}
```

> **Подробная документация:** [Работа с failed messages в Symfony Messenger](failed-messages.md)

### Проверки на Worker-сервере

```bash
# Проверка воркеров
sudo supervisorctl status

# Проверка логов
sudo tail -20 /var/log/task/messenger/*.log
```

#### Проверка новых воркеров и очередей в RabbitMQ

После релиза новых воркеров (новых Messenger transports) необходимо проверить:

```bash
# Проверить, что воркер запущен
sudo supervisorctl status | grep <имя_воркера>

# Проверить логи нового воркера
sudo tail -20 /var/log/task/messenger/<имя_воркера>.log

# Проверить error-логи
sudo tail -20 /var/log/task/messenger/<имя_воркера>.error.log

# Проверить очередь в RabbitMQ (выполнить на сервере RabbitMQ)
rabbitmqctl list_queues -p task | grep <имя_транспорта>
```

> **Если воркер имеет статус `FATAL Exited too quickly`:**
>
> 1. Проверьте error-лог воркера для получения деталей ошибки
> 2. Вероятная причина: отсутствует переменная `*_TRANSPORT_DSN` в `.env.local`
> 3. Добавьте нужную переменную в `.env.local` и перезапустите воркер
>
> > Подробная информация: [Настройка Supervisor воркеров](setup-worker-production/supervisor-services.md)

### Проверка соотвествия версий

Выполнить на обоих серверах
```bash
cd /var/www/task && sudo -u wwwtask git rev-parse HEAD
cat /var/www/task/var/build/version
```

## 4. Rollback

### Откат Web-сервера

```bash
# Откат к предыдущему тегу
cd /var/www/task && sudo -u wwwtask git checkout <previous-tag>
cd /var/www/task && sudo -u wwwtask make install

# Откат миграций (если нужно)
cd /var/www/task && sudo -u wwwtask php bin/console doctrine:migrations:migrate prev --no-interaction

# Перезагрузка сервисов
sudo systemctl reload php-fpm && sudo systemctl reload nginx
```

### Откат Worker-сервера

```bash
# Откат к предыдущему тегу
cd /var/www/task && sudo -u wwwtask git checkout <previous-tag>
cd /var/www/task && sudo -u wwwtask make install

# Перезапуск воркеров
sudo supervisorctl stop task-workers:* && sudo supervisorctl start task-workers:*
sudo supervisorctl status
```

**Порядок отката:** Workers → Web (обратный порядок деплоя).
