# Конфигурация nginx и PHP-FPM

## Настройка веб-сервера (nginx)

### Пример конфига nginx

```nginx
server {
    listen 443 ssl http2;
    server_name task.ai-aid.pro;

    root        /var/www/task/apps/web/public;
    index       index.php;

    access_log  /var/log/nginx/task-access.log;
    error_log   /var/log/nginx/task-error.log;

    proxy_read_timeout 600s;
    proxy_connect_timeout 600s;
    proxy_send_timeout 600s;
    fastcgi_read_timeout 600s;
    send_timeout 600s;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    # артефакты файлов источника через X-Accel-Redirect (локальный диск)
    location /artifact-files/ {
        internal;
        alias /mnt/task/files/data-source/;
    }

    # кэш Source workspace (используется приложением при STORAGE_DRIVER=s3)
    location /artifact-cache/ {
        internal;
        alias /var/www/task/var/cache/source-workspace/;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php-fpm/wwwtask.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }
}
```

Ключевые моменты:

- `location /artifact-files/` должен быть `internal` и указывать на директорию, где PHP складывает артефакты (`SOURCE_STORAGE_DIR`).
- `location /artifact-cache/` должен быть `internal` и указывать на `SOURCE_FILE_WORKSPACE_CACHE_DIR`.
- второй `location ~ \.php$` (с `return 404;`) блокирует доступ к PHP‑файлам напрямую.
- ssl/redirect секции остаются без изменений, но важно не забывать про `proxy_*_timeout` при длительных фоновых операциях.

## Пул PHP-FPM

```ini
[wwwtask]
user = wwwtask
group = wwwtask
listen = /run/php-fpm/wwwtask.sock
listen.acl_users = nginx
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
php_admin_value[memory_limit] = 256M
env[PATH] = /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
```

Отдельный пул облегчает настройку прав и ограничений. `listen.acl_users = nginx` позволяет nginx работать с сокетом без `chmod 777`.

### Права на директорию артефактов

PHP создаёт каталоги/файлы в `SOURCE_STORAGE_DIR` через `Common\Component\FileStorage\FileStorage` с правами `0770/0660`. Чтобы nginx смог отдавать артефакты через alias:

1. Добавьте пользователя nginx в группу, от которой работает PHP-FPM:

   ```bash
   sudo usermod -a -G wwwtask nginx
   sudo systemctl restart nginx php-fpm
   ```

2. Убедитесь, что сама директория принадлежит этой группе и имеет `g+rx`:

   ```bash
   sudo chgrp -R wwwtask /mnt/task/files/data-source
   sudo chmod -R g+rx /mnt/task/files/data-source
   ```

3. Для проверки используйте `sudo -u nginx stat /mnt/task/files/data-source/<relative-path>` — команда не должна падать с `Permission denied`.

Если требуется более жёсткий контроль прав, можно ослабить режимы создания в `FileStorage`, но это требует кода‑ревью и деплоя; инфраструктурное решение через группы предпочтительнее.

### Что ещё проверить

- После изменения конфигов перезапускайте и nginx, и PHP‑FPM (`systemctl restart nginx php-fpm`) — иначе новые ACL/группы не применятся.
- Прогоните `curl -I https://task.ai-aid.pro/source/<uuid>/artifact/html/preview` для реального источника: заголовки должны содержать `X-Accel-Redirect` и статус `200`.
- Контролируйте логи `/var/log/nginx/task-error.log` и `var/log/web/prod.log` сразу после деплоя — это помогает поймать регрессии раньше пользователей.

## Примечание

Этот документ содержит настройки инфраструктуры для production окружения.
Актуальная инструкция по деплою: [Деплой в продакшен](../deploy-production.md)
