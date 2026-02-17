# Тестирование системы контроля версий воркеров

Этот документ описывает как протестировать функциональность авто-перезапуска воркеров при изменении версии приложения.

## Smoke-тест

### Цель

Проверить что старый воркер останавливается при получении сообщения с новой версией, а новый воркер успешно обрабатывает это сообщение.

### Шаги тестирования

#### 1. Подготовка окружения

```bash
# Убедитесь что RabbitMQ запущен
docker compose up -d rabbitmq

# Сгенерируйте начальную версию
bin/console app:build-version

# Запустите воркер с текущей версией в фоновом режиме
bin/console messenger:consume source_download --env=dev --no-interaction --sleep=1 --memory-limit=512M --verbose &
WORKER_PID=$!
echo "Worker PID: $WORKER_PID"
```

#### 2. Отправка тестового сообщения

```bash
# Отправьте тестовое сообщение в очередь
bin/console messenger:dispatch Common\Module\Source\Application\UseCase\Command\Source\Download\DownloadCommand '{"sourceUuid":"test-smoke-uuid"}' --bus=command.bus
```

#### 3. Изменение версии

```bash
# Сгенерируйте новую версию (имитация деплоя)
bin/console app:build-version --build-version=new-test-version-$(date +%s)

# Или измените файл версии вручную
echo "new-test-version-$(date +%s)" > var/build/version
```

#### 4. Отправка сообщения с новой версией

```bash
# Отправьте еще одно сообщение
bin/console messenger:dispatch Common\Module\Source\Application\UseCase\Command\Source\Download\DownloadCommand '{"sourceUuid":"test-smoke-new-version"}' --bus=command.bus
```

#### 5. Проверка результатов

```bash
# Проверьте логи воркера
docker compose logs worker-cli

# Проверьте что старый воркер остановился
ps aux | grep "messenger:consume" | grep -v grep

# Проверьте сообщения в очереди
docker compose exec rabbitmq rabbitmqctl list_queues -p task name messages

# Запустите новый воркер и проверьте что он обработает сообщение
bin/console messenger:consume source_download --env=dev --no-interaction --sleep=1 --memory-limit=512M --verbose
```

### Ожидаемый результат

1. Старый воркер должен остановиться при получении сообщения с новой версией
2. В логах должна появиться ошибка `BuildVersionMismatchException`
3. Новый воркер должен успешно обработать сообщение с новой версией
4. Сообщение должно остаться в очереди для повторной обработки

## Unit-тесты

Запустите smoke-тесты:

```bash
# Запуск интеграционных тестов
vendor/bin/phpunit tests/Integration/Infrastructure/Component/Messenger/BuildVersionSmokeTest.php

# Запуск всех тестов связанных с версией
vendor/bin/phpunit tests/Unit/Infrastructure/Component/Messenger/ --filter=BuildVersion
```

## Отладка

### Проверка версии

```bash
# Проверить текущую версию
bin/console app:build-version --show

# Проверить версию из файла
cat var/build/version

# Проверить версию из переменной окружения
echo $APP_BUILD_VERSION
```

### Логирование

Включите детальное логирование для отладки:

```bash
# В .env.local добавьте
LOG_LEVEL=debug
```

### Проверка middleware

Убедитесь что middleware зарегистрированы правильно:

```bash
# Проверить конфигурацию Messenger
bin/console debug:config messenger
```

## Автоматизация

### CI/CD интеграция

Добавьте в CI pipeline проверку:

```yaml
# .github/workflows/build-version-test.yml
name: Build Version Test
on: [push, pull_request]

jobs:
  test-build-version:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      - name: Generate build version
        run: bin/console app:build-version
      - name: Run smoke tests
        run: vendor/bin/phpunit tests/Integration/Infrastructure/Component/Messenger/BuildVersionSmokeTest.php
```

### Мониторинг в проде

Настройте алерты для:
- `BuildVersionMismatchException` в логах
- Остановленных воркеров
- Сообщений в failure очереди с причиной версии

## Возврат к предыдущей версии

Если нужно откатить изменения:

```bash
# Установить конкретную версию вручную
echo "previous-version-hash-timestamp" > var/build/version

# Перезапустить воркеров
sudo systemctl restart task-worker-*
```

> Директория `var/` не хранится в git, поэтому версию восстанавливайте вручную через перезапись файла.
