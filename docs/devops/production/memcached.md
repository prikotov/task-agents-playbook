# MEMCACHED

## Обзор использования MEMCACHED в проекте TasK

MEMCACHED в проекте TasK используется как высокопроизводительное распределенное хранилище кэша для следующих целей:

1. **Кэширование эмбеддингов** (`cache.embeddings`) — хранение векторных представлений текста для RAG-системы.
2. **Кэширование результатов чата** (`cache.chat_complete`) — хранение завершённых ответов чата для ускорения повторных запросов.
3. **Rate limiting** (`cache.rate_limiter`) — ограничение частоты запросов к API.
4. **Блокировки** (`framework.lock`) — механизм блокировок Symfony для предотвращения гонки состояний.

В dev-окружении используется файловая система вместо MEMCACHED для упрощения разработки.

## Архитектурные рекомендации по развертыванию

### Общий экземпляр для web и worker нод

Рекомендуется использовать один общий экземпляр MEMCACHED для всех компонентов системы:

```
┌─────────────────┐    ┌─────────────────┐
│   Web-нода      │    │   Worker-нода   │
│                 │    │                 │
└─────────┬───────┘    └─────────┬───────┘
          │                      │
          └──────────┬───────────┘
                     │
          ┌──────────────────────┐
          │   Общий Memcached    │
          │   (memcached://      │
          │   memcached-host:    │
          │   11211)             │
          └──────────────────────┘
```

- **Web-ноды** используют MEMCACHED для кэширования результатов чата и rate limiting
- **Worker-ноды** используют MEMCACHED для кэширования эмбеддингов при обработке документов
- **API-ноды** используют MEMCACHED для rate limiting и блокировок

Такой подход обеспечивает:

1. **Эффективное использование ресурсов** — один экземпляр обслуживает все ноды.
2. **Согласованность кэша** — все компоненты имеют доступ к одинаковым данным.
3. **Упрощение администрирования** — единая точка конфигурации и мониторинга.

### Рекомендации по производительности

- Выделяйте не менее 1 ГБ оперативной памяти для MEMCACHED в небольших инсталляциях
- Для средних и крупных инсталляций рекомендуется 2-4 ГБ
- Размещайте MEMCACHED на отдельном сервере или на сервере с минимальной нагрузкой
- Используйте сетевое подключение с низкой задержкой до web и worker нод

## Пошаговая инструкция по установке и настройке MEMCACHED

### Установка на Linux (AlmaLinux/CentOS/Fedora)

```bash
# Установка MEMCACHED
sudo dnf install memcached

# Установка PHP-расширения
sudo dnf install php-pecl-memcached

# Включение и запуск сервиса
sudo systemctl enable --now memcached
sudo systemctl status memcached
```

### Базовая настройка

Основной файл конфигурации: `/etc/sysconfig/memcached`.

Пример базовой конфигурации для production:

```bash
# /etc/sysconfig/memcached
PORT="11211"
USER="memcached"
MAXCONN="1024"
CACHESIZE="2048"
OPTIONS="-l 0.0.0.0 -I 5m"
```

Где основные параметры:
- `PORT` — порт MEMCACHED (по умолчанию `11211`).
- `CACHESIZE` — объём памяти в МБ (например, `2048` = 2 ГБ).
- `MAXCONN` — максимальное количество одновременных подключений.
- `OPTIONS` — дополнительные настройки, например:
  - `-l 0.0.0.0` — слушать на всех интерфейсах;
  - `-I 5m` — увеличить максимальный размер одного объекта (по умолчанию 1 МБ). Нужно далеко не всегда и может вести к перерасходу памяти.

```bash
# После изменения конфигурации и firewall перезапустите сервис MEMCACHED:
sudo systemctl restart memcached
```

### Настройка файрвола

```bash
# Для firewalld: открываем порт
sudo firewall-cmd --add-port=11211/tcp --permanent
sudo firewall-cmd --reload

# Проверка, что порт добавлен
sudo firewall-cmd --list-ports
# или
sudo firewall-cmd --list-all
```

## Конфигурация для подключения из web и worker нод

### Переменные окружения

Добавьте в `.env.local` на всех нодах:

```dotenv
# DSN для подключения к MEMCACHED
MEMCACHED_DSN=memcached://memcached-host:11211
```

Где `memcached-host` — IP-адрес или доменное имя сервера MEMCACHED.

### Проверка подключения

```bash
# Проверка из PHP
php -r "echo (new \Memcached())->getVersion();"

# Проверка через telnet
telnet memcached-host 11211
# Введите: stats
```

## Рекомендации по мониторингу и обслуживанию

### Мониторинг производительности

1. **Использование памяти**:
   ```bash
   echo "stats" | nc localhost 11211 | grep bytes
   ```

2. **Количество подключений**:
   ```bash
   echo "stats" | nc localhost 11211 | grep curr_connections
   ```

3. **Hit ratio** (коэффициент попадания в кэш):
   ```bash
   echo "stats" | nc localhost 11211 | grep -E "(get_hits|get_misses|cmd_get)"
   # Hit ratio = get_hits / (get_hits + get_misses)
   ```

### Алерты и метрики

Настройте мониторинг для следующих метрик:

- **Использование памяти** > 90% от выделенной
- **Hit ratio** < 80% (может указывать на недостаточный объем памяти)
- **Количество ошибок подключения** > 5% от общего числа
- **Время отклика** > 100мс

### Обслуживание

1. **Регулярная очистка статистики**:
   ```bash
   echo "stats reset" | nc localhost 11211
   ```

2. **Перезапуск при изменении конфигурации**:
   ```bash
   sudo systemctl restart memcached
   ```

3. **Мониторинг логов**:
   ```bash
   sudo journalctl -u memcached -f
   ```

### Скрипт для мониторинга

Для мониторинга состояния MEMCACHED используется скрипт в директории проекта:

```bash
# Запуск мониторинга
./bin/memcached-monitor

# Или с указанием хоста и порта
MEMCACHED_HOST=memcached.internal MEMCACHED_PORT=11211 ./bin/memcached-monitor
```

Скрипт находится в двух файлах:
- [`devops/scripts/memcached-monitor.sh`](../../../devops/scripts/memcached-monitor.sh) — основной скрипт мониторинга.
- [`bin/memcached-monitor`](../../../bin/memcached-monitor) — обёртка для удобного запуска.

Для регулярного мониторинга добавьте в cron:
```bash
# Добавить в crontab
*/5 * * * * /path/to/project/bin/memcached-monitor >> /var/log/memcached-monitor.log
