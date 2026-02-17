# Хранилище файлов

## Возможности и архитектура

- Все сервисы работают через `Common\Component\FileStorage\FileStorageInterface`. Базовая реализация `FileStorage` пишет в локальную файловую систему; `Common\Component\FileStorage\Flysystem\FlysystemFileStorage` добавляет поддержку любого адаптера Flysystem (локальный диск, AWS S3, MinIO и т.д.).
- На один и тот же интерфейс завязаны разные домены: загрузка исходных источников (`SOURCE_STORAGE_DIR`), пользовательские аватары (`AVATAR_STORAGE_DIR`), документы и чанк‑файлы RAG (`DOCUMENT_STORAGE_DIR`, `CHUNK_STORAGE_DIR`).
- Драйвер выбирается переменной `STORAGE_DRIVER`: `local` (каталоги) или `s3` (MinIO/S3 через `S3FileStorage`). Логика переключения — в `config/packages/storage.php`.
- Use case-и получают `FileStorageService` своего модуля. Сервис умеет:
  - сохранять файл с диска (`store`) или из строки (`storeContent`);
  - генерировать уникальные имена и резервировать директории (`generateStorageFilename`, `getStorageFilename`);
  - читать контент и метаданные (`getContent`, `getFileMeta`);
  - проверять наличие и удалять файлы, искать по маске.
- Поверх Flysystem можно добавлять кэш, лимитировать права доступа и подключать новые бекенды, не меняя код домена.

### Переменные окружения

| Контекст | Переменная | Описание |
| --- | --- | --- |
| Source (сырые артефакты) | `SOURCE_STORAGE_DIR` | Базовый каталог/префикс для файлов источников |
| Source (документы) | `DOCUMENT_STORAGE_DIR` | Каталог/префикс с обработанными документами |
| Source (чанки) | `CHUNK_STORAGE_DIR` | Каталог/префикс, где лежат чанки для RAG |
| User Avatar | `AVATAR_STORAGE_DIR` | Каталог/префикс с аватарами пользователей |
| Storage driver | `STORAGE_DRIVER` | `local` или `s3` (S3/MinIO) |

Для Flysystem вариант на S3 также потребует набор переменных с учётными данными (см. раздел ниже).

## Настройка локального файлового хранилища

1. Выберите директории, доступные процессу PHP (обычно `www-data`). Пример:

   ```dotenv
   SOURCE_STORAGE_DIR=/var/lib/task/storage/source
   DOCUMENT_STORAGE_DIR=/var/lib/task/storage/documents
   CHUNK_STORAGE_DIR=/var/lib/task/storage/chunks
   AVATAR_STORAGE_DIR=/var/lib/task/storage/avatars
   ```

2. Создайте каталоги и задайте права 0770 для директорий и 0660 для файлов. Эти значения соответствуют `PortableVisibilityConverter`, который используется в `LocalFilesystemFactory`:

   ```bash
   sudo install -d -m 0770 -o www-data -g www-data /var/lib/task/storage/{source,documents,chunks,avatars}
   ```

3. Проверьте, что Symfony сервисы ссылаются на `Common\Component\FileStorage\Flysystem\FlysystemFileStorage`. Для локального диска ничего менять не нужно: фабрика автоматически создаёт `LocalFilesystemAdapter` на основе переменных окружения.

4. (Опционально) Настройте Nginx для раздачи артефактов, как описано в [`../devops/production/deploy-production.md`](../devops/production/deploy-production.md) — через `internal` alias на каталоги в `SOURCE_STORAGE_DIR`.

### Быстрая проверка локальной конфигурации

```bash
APP_ENV=dev php bin/console app:file-storage:cleanup --mode=list
```

Команда проходится по всем бакетам, проверяя доступность файлов и выводя список сирот, поэтому сразу видно проблемы с правами или путями.

## Настройка S3 / MinIO

> MinIO сервис присутствует только в dev-профиле `docker compose`; для prod используйте внешний S3 совместимый сервис.
> Источники типа `github` пока требуют локального хранилища (работа с директориями); в S3-режиме не поддержаны.

### Быстрый старт для dev (MinIO)

1. Создайте `.env.local` на основе [`../devops/production/storage/minio.dev.env.example`](../devops/production/storage/minio.dev.env.example) (пути `./var/storage/...`, `STORAGE_DRIVER=s3`, креды MinIO).
2. Запустите MinIO и инициализацию бакетов:
   ```bash
   docker compose --profile dev up -d minio minio-init
   ```
3. Запустите приложение/воркеры — они будут работать через S3 API, но файлы останутся в тех же каталогах на хосте.

### Переменные окружения

```dotenv
STORAGE_DRIVER=s3
STORAGE_S3_REGION=us-east-1
STORAGE_S3_ENDPOINT=http://minio:9000                  # MinIO; для AWS можно оставить https://s3.amazonaws.com
STORAGE_S3_KEY=minioadmin
STORAGE_S3_SECRET=minioadmin

STORAGE_S3_BUCKET_SOURCE=task-source
STORAGE_S3_BUCKET_DOCUMENT=task-documents
STORAGE_S3_BUCKET_CHUNK=task-chunks
STORAGE_S3_BUCKET_AVATAR=task-avatars
```

Для хранения нескольких окружений в одном бакете можно использовать разные бакеты с префиксами в именах (например, `task-source-dev`, `task-source-prod`).

### Встроенная конфигурация (storage.php)

- `STORAGE_DRIVER=local` — `FlysystemFileStorage` на каталоги `*_STORAGE_DIR`.
- `STORAGE_DRIVER=s3` — `S3FileStorage` + `Aws\S3\S3Client` (`use_path_style_endpoint=true` для MinIO).

`rootPath` формируется как `s3://<bucket>/<prefix>/` и используется в логах/метаданных. При работе с MinIO воспользуйтесь инструкцией [`../devops/production/storage/minio.md`](../devops/production/storage/minio.md) для развёртывания контейнера и создания бакетов.

### Чек-лист миграции

1. Создайте бакеты и включите versioning / lifecycle по политике проекта.
2. Пропишите политики доступа (минимум `s3:PutObject`, `s3:GetObject`, `s3:DeleteObject`, `s3:ListBucket`).
3. Обновите `.env` и `services_storage_s3.yaml`, задеплойте.
4. Выполните smoke-тесты:
   ```bash
   php bin/console app:source:download --limit=1 --project-uuid=<uuid>
   php bin/console app:file-storage:cleanup --mode=list
   ```
   После скачивания один из источников должен разместить артефакты в бакете; команда очистки подтвердит, что сервис видит файлы и умеет читать метаданные.
5. Настройте lifecycle-политику или `apps/console/src/Module/Source/Command/CleanupFileStorageCommand.php` в расписании, чтобы удалять сиротские артефакты.

## Эксплуатационные заметки

- `apps/console/src/Module/Source/Command/CleanupFileStorageCommand.php` помогает удалить файлы, на которые больше нет ссылок в БД. Перед запуском на бою убедитесь, что переменные окружения указывают на правильное хранилище; команда использует `FileStorageService` и будет работать как с диском, так и с S3.
- При расширении функциональности добавляйте новые бакеты через отдельные сервисы и переменные окружения — смешивать контексты в одном каталоге запрещено правилами архитектуры.
- Любое временное изменение (например, принудительное хранение в локальной папке) помечайте `@todo` и фиксируйте в `todo`, чтобы не забыть вернуть конфигурацию.
