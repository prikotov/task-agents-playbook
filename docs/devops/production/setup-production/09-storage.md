# 08. Файловое хранилище

В проекте используется два основных режима работы с файлами: `local` (локальный диск) и `s3` (MinIO/AWS S3). В обоих случаях для отдачи файлов пользователю используется механизм `X-Accel-Redirect`.

## Подготовка директорий (для STORAGE_DRIVER=local)

```bash
# подготовка базовых директорий
sudo install -d -m 775 -o wwwtask -g wwwtask /mnt/task/files/{data-source,documents,chunks,avatars}

# установка прав
sudo chown -R wwwtask:wwwtask /mnt/task/files && sudo chmod -R g+rwX /mnt/task/files
```

## Подготовка кэша (для STORAGE_DRIVER=s3)

При работе с S3 воркеры и приложение могут использовать локальный кэш (`SOURCE_FILE_WORKSPACE_MODE=cache`). Директория кэша находится внутри проекта:

```bash
sudo install -d -m 775 -o wwwtask -g wwwtask /var/www/task/var/cache/source-workspace
```

## Конфигурация Nginx

Для того чтобы Nginx мог отдавать файлы, закэшированные приложением из S3, необходимо добавить соответствующую локацию в конфиг хоста.

`/etc/nginx/conf.d/task.ai-aid.pro.conf`:
```nginx
server {
    # ... остальные настройки ...

    # Локация для файлов на диске (STORAGE_DRIVER=local)
    location /artifact-files/ {
        internal;
        alias /mnt/task/files/data-source/;
    }

    # Локация для кэша S3 (STORAGE_DRIVER=s3 + SOURCE_FILE_WORKSPACE_MODE=cache)
    # Приложение скачивает файл из S3 в эту папку, а Nginx отдает его пользователю
    location /artifact-cache/ {
        internal;
        alias /var/www/task/var/cache/source-workspace/;
    }

    # ... остальное ...
}
```

### Права доступа для Nginx

Пользователь `nginx` должен иметь доступ на чтение к папкам хранилища и кэша. Самый простой способ — добавить его в группу `wwwtask`:

```bash
sudo usermod -a -G wwwtask nginx
sudo systemctl reload nginx
```

## Перенос данных (Migration)

Если требуется перенос данных между серверами:
```bash
rsync -aH --no-owner --no-group --no-times --partial --info=progress2 -e "ssh -o Compression=no" /mnt/task/files/ user@production-server:/mnt/task/files/
```

