# Версионирование кода

## Обзор

В проекте используется механизм версионирования кода для защиты от несогласованности между Web и Worker серверами. Каждый деплой создаёт уникальную версию сборки, которая проверяется при обработке сообщений в очередях.

Механизм реализован через `BuildVersionGuardMiddleware`, который предотвращает обработку сообщений воркерами, если версия кода воркера старше версии сообщения.

## Формат версии

Версия имеет формат:

```
{git-SHA}-{timestamp}
```

Пример: `8efd6eaa06132763757365a20563ac246f6cc4e9-1770777877`

- **git-SHA**: полный хеш коммита (40 символов)
- **timestamp**: Unix timestamp генерации версии

## Логика сравнения версий

Сравнение версий выполняется в [`BuildVersionGuardMiddleware::isMessageFromNewerBuild()`](../../src/Infrastructure/Component/Messenger/Middleware/BuildVersionGuardMiddleware.php:79)

| Условие | Версия сообщения | Версия воркера | Результат |
|---------|------------------|----------------|-----------|
| Git SHA совпадают | `abc123-1000` | `abc123-2000` | ✅ Обработка разрешена (timestamp игнорируется) |
| Git SHA разные | `def456-3000` | `abc123-1000` | ✅ Обработка разрешена (новее по timestamp) |
| Git SHA разные | `abc123-1000` | `def456-3000` | ❌ Ошибка: `BuildVersionMismatchException` |
| Git SHA разные, одинаковый timestamp | `abc123-1000` | `def456-1000` | Лексикографическое сравнение |

**Ключевые правила:**

1. **Приоритет логики**: commit-часть → timestamp → полное строковое сравнение
2. Если **git SHA совпадают** → версии считаются равными, timestamp игнорируется
3. Различие **timestamps допустимо** только при совпадении git SHA
4. Если **git SHA разные** и сообщение новее воркера → ошибка `BuildVersionMismatchException`

## Генерация версии

Версия генерируется автоматически через команду `make install`:

```bash
make install
```

Эта команда запускает `bin/console app:build-version`, который:

1. Получает текущий git SHA: `git rev-parse HEAD`
2. Получает текущий timestamp
3. Формирует версию в формате `{git-SHA}-{timestamp}`
4. Записывает версию в файл `var/build/version`

**Важно:** Любое изменение кода (включая документацию) создаёт новый git commit → меняет версию.

## Последствия для деплоя

### Требования к деплою

- **Требуется одновременный деплой на оба сервера** (сначала Web, затем Workers)
- Любое изменение кода меняет версию
- Различие timestamps между серверами допустимо **только** при совпадении git SHA

### При несовпадении версий

Если версии не совпадут, воркеры остановятся с ошибкой:

```
BuildVersionMismatchException: Message build version {version} is newer than worker build version {version}
```

Это предотвращает обработку сообщений с устаревшим воркером и защищает от несогласованности данных.

## Команды проверки

### Проверка текущей версии на сервере

```bash
sudo -u wwwtask cat /var/www/task/var/build/version
```

### Проверка git SHA (должен совпадать с первой частью версии)

```bash
sudo -u wwwtask git rev-parse HEAD
```

### Сравнение версий между серверами

Выполнить на обоих серверах:

```bash
cd /var/www/task && sudo -u wwwtask git rev-parse HEAD
cat /var/www/task/var/build/version
```

Git SHA должны совпадать. Timestamps могут отличаться (допустимо).

## Технические детали

### Ключевые файлы и классы

| Компонент | Путь | Назначение |
|-----------|------|-----------|
| Команда генерации версии | [`apps/console/src/Command/BuildVersionCommand.php`](../../apps/console/src/Command/BuildVersionCommand.php) | Генерация версии сборки |
| Провайдер версии | [`src/Infrastructure/Component/Messenger/Service/BuildVersionProvider.php`](../../src/Infrastructure/Component/Messenger/Service/BuildVersionProvider.php) | Получение текущей версии |
| Middleware-проверка | [`src/Infrastructure/Component/Messenger/Middleware/BuildVersionGuardMiddleware.php`](../../src/Infrastructure/Component/Messenger/Middleware/BuildVersionGuardMiddleware.php) | Проверка версий в воркерах |
| Middleware-добавление | [`src/Infrastructure/Component/Messenger/Middleware/BuildVersionMiddleware.php`](../../src/Infrastructure/Component/Messenger/Middleware/BuildVersionMiddleware.php) | Добавление версии к сообщениям |

### Файл версии

- Путь: `var/build/version`
- Генерируется при каждом `make install`
- Автоматически добавляется в `.gitignore`

### Версия в сообщениях

Каждое сообщение в очереди автоматически получает заголовок `X-Build-Version` при отправке через [`BuildVersionMiddleware`](../../src/Infrastructure/Component/Messenger/Middleware/BuildVersionMiddleware.php:17).

## Полезные ссылки

- [Релиз на два сервера (Web + Workers)](deploy-production.md)
