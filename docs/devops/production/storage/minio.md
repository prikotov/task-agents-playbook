# MinIO для хранения артефактов

## Сценарии

- **Production (fresh install)**: установка MinIO → создание бакетов → настройка приложения → проверка.
- **Production (migration)**: установка MinIO и бакетов → подготовка env → остановка writer‑сервисов → миграция → переключение приложения на S3 → проверка.

## Production: установка MinIO (Podman)

Ниже пример минимальной установки MinIO на отдельном production‑хосте через Podman.  
Дальше приложение подключается к MinIO через переменные `STORAGE_S3_*`.

### Предварительно

- Установлен Podman (`podman >= 4.x`).
- Создан системный пользователь `wwwtask` для rootless Podman (см. [../../wwwtask-user-setup.md](../../wwwtask-user-setup.md)).
- Выделена директория под данные MinIO (диск с нужным объёмом).

### Шаги

#### 1) Данные и env

```bash
# Директория данных MinIO на отдельном диске/разделе.
sudo install -d -m 755 -o wwwtask -g wwwtask /mnt/task/minio-data

# Важно: MinIO запускается rootless от пользователя `wwwtask` и должен иметь write-доступ к `${MINIO_DATA_ROOT}`.
# Если директория уже существовала и оказалась с владельцем `root:root` — сервис будет падать с ошибкой
# `Unable to write to the backend` / `file access denied`. Исправление:
#   sudo chown -R wwwtask:wwwtask /mnt/task/minio-data
sudo -u wwwtask -H bash -lc 'touch /mnt/task/minio-data/.write-test && rm /mnt/task/minio-data/.write-test'

# Шаблон лежит в репозитории: minio.env (без production-секретов).
# Создайте локальный env-файл с секретами в корне проекта (/var/www/task).
sudo install -m 600 -o wwwtask -g wwwtask /var/www/task/minio.env /var/www/task/minio.env.local
sudo -u wwwtask -H ${EDITOR:-vi} /var/www/task/minio.env.local
```

#### 2) Автозапуск при перезагрузке (systemd user unit)

```bash
# Нужно для автозапуска user unit после перезагрузки сервера.
sudo loginctl enable-linger wwwtask

# На части production-хостов systemd user manager для пользователя не поднят, пока не будет "сессии".
# В этом случае `systemctl --user ...` падает с ошибкой вида:
# `Failed to connect to bus: No medium found` / `Носитель не найден`.
# Команда ниже поднимает `user@<uid>.service` и создаёт runtime-директорию `/run/user/<uid>`.
# Важно: НЕ используйте переменную `UID` — в bash она зарезервирована (readonly) и содержит UID текущего пользователя.
WTASK_UID=$(id -u wwwtask)
sudo systemctl start "user@${WTASK_UID}.service"

sudo -u wwwtask -H mkdir -p /home/wwwtask/.config/systemd/user

# Важно:
# - `EnvironmentFile=...` задаёт переменные окружения для процесса `podman`, но не прокидывает их внутрь контейнера.
# - Чтобы MinIO применил `MINIO_ROOT_USER`/`MINIO_ROOT_PASSWORD`, передайте env-файлы в `podman run` через `--env-file`.
# - Rootless Podman (опционально): чтобы UID/GID внутри контейнера совпадал с `wwwtask`, используйте
#   `--userns=keep-id --user %U:%G` (где `%U/%G` — systemd specifiers).
sudo -u wwwtask -H tee /home/wwwtask/.config/systemd/user/task-minio.service >/dev/null <<'EOF'
[Unit]
Description=TasK MinIO
Wants=network-online.target
After=network-online.target

[Service]
Type=simple
EnvironmentFile=/var/www/task/minio.env
EnvironmentFile=-/var/www/task/minio.env.local

ExecStart=/usr/bin/podman run --rm \
  --env-file /var/www/task/minio.env \
  --env-file /var/www/task/minio.env.local \
  --userns=keep-id --user %U:%G \
  --name task-minio \
  -p 9000:9000 -p 9001:9001 \
  -v ${MINIO_DATA_ROOT}:/data:Z \
  quay.io/minio/minio:RELEASE.2024-08-29T01-40-52Z server /data --console-address ":9001"

ExecStop=/usr/bin/podman stop -t 10 task-minio
ExecStopPost=/usr/bin/podman rm -f task-minio
Restart=always
RestartSec=5

[Install]
WantedBy=default.target
EOF

sudo -u wwwtask -H XDG_RUNTIME_DIR="/run/user/${WTASK_UID}" systemctl --user daemon-reload
sudo -u wwwtask -H XDG_RUNTIME_DIR="/run/user/${WTASK_UID}" systemctl --user enable --now task-minio.service
```

#### 3) Проверка

```bash
sudo -u wwwtask -H XDG_RUNTIME_DIR="/run/user/$(id -u wwwtask)" /var/www/task/bin/minio status
sudo -u wwwtask -H XDG_RUNTIME_DIR="/run/user/$(id -u wwwtask)" /var/www/task/bin/minio logs -f
```

`bin/minio logs` выводит логи контейнера через `podman logs` (не требует доступа к system journal).

#### Firewall (firewalld)

Откройте порты MinIO на production-хосте:

```bash
sudo firewall-cmd --add-port=9000/tcp --permanent
sudo firewall-cmd --add-port=9001/tcp --permanent  # если нужен WebUI/console
sudo firewall-cmd --reload
```

Проверить, что порты добавлены в правила firewalld:

```bash
sudo firewall-cmd --list-ports
sudo firewall-cmd --zone=public --list-ports
```

Также можно убедиться, что порты реально слушаются локально:

```bash
sudo ss -tulpn | grep -E ':9000|:9001'
```

Если `bin/minio logs` пишет `no container with name ...`, а `status` показывает `auto-restart (Result: exit-code)`,
значит контейнер не стартует и сразу удаляется (из-за `podman run --rm`). В этом случае смотрите причину падения
через ручной запуск `podman run` (stderr) с подгрузкой env:

```bash
sudo -u wwwtask -H bash -lc '
  set -euo pipefail
  set -a
  source /var/www/task/minio.env
  [ -f /var/www/task/minio.env.local ] && source /var/www/task/minio.env.local
  set +a

  exec /usr/bin/podman run --rm --name task-minio \
    -p 9000:9000 -p 9001:9001 \
    -v ${MINIO_DATA_ROOT}:/data:Z \
    quay.io/minio/minio:RELEASE.2024-08-29T01-40-52Z server /data --console-address ":9001"
'
```

Проверьте health:

```bash
curl -f http://localhost:9000/minio/health/live
```

## Production: бакеты и доступ

Создайте бакеты (имена должны совпадать с `STORAGE_S3_BUCKET_*`) и закройте анонимный доступ.

```bash
sudo -u wwwtask -H /var/www/task/bin/minio create-bucket task-source
sudo -u wwwtask -H /var/www/task/bin/minio create-bucket task-documents
sudo -u wwwtask -H /var/www/task/bin/minio create-bucket task-chunks
sudo -u wwwtask -H /var/www/task/bin/minio create-bucket task-avatars
```

Если имена бакетов отличаются — создайте бакеты с вашими именами (они должны совпадать с `STORAGE_S3_BUCKET_*` в
конфигурации приложения) и выполните команды `create-bucket` для каждого бакета.

### Рекомендуется: отдельный MinIO user для приложения

Не используйте `MINIO_ROOT_USER`/`MINIO_ROOT_PASSWORD` в приложении. Создайте отдельный MinIO user (его access key/secret
и будут значениями `STORAGE_S3_KEY`/`STORAGE_S3_SECRET`).

Проще всего делать это тем же способом, что и создание бакетов: через `bin/minio`, который запускает `mc` в контейнере.

Команда `setup-app-user`:

- создаёт/пере-создаёт policy `task-app` из файла `devops/minio/task-app-policy.json`;
- создаёт MinIO user, если его ещё нет;
- привязывает policy к user.

Выполняйте команды на production-хосте, где запущен MinIO, в shell пользователя `wwwtask` (rootless Podman), например:
`sudo -iu wwwtask`.

```bash
cd /var/www/task

# Эти значения используйте в env приложения/воркеров как STORAGE_S3_KEY / STORAGE_S3_SECRET.
STORAGE_S3_KEY='<access-key>'
STORAGE_S3_SECRET='<secret-key>'

./bin/minio setup-app-user "$STORAGE_S3_KEY" "$STORAGE_S3_SECRET"
```

После этого используйте `STORAGE_S3_KEY`/`STORAGE_S3_SECRET` в конфигурации приложения/воркеров.

### Как добавлять бакеты и права на них

1) Создайте новый бакет:

```bash
sudo -u wwwtask -H /var/www/task/bin/minio create-bucket <bucket-name>
```

2) Добавьте бакет в policy-файл `devops/minio/task-app-policy.json` (в оба списка: `arn:aws:s3:::<bucket>` и
`arn:aws:s3:::<bucket>/*`).

3) Примените обновлённую policy:

```bash
cd /var/www/task
./bin/minio setup-app-user "$STORAGE_S3_KEY" "$STORAGE_S3_SECRET"
```

## Production: настройка приложения

1) В конфигурации приложения/воркеров укажите:

```dotenv
STORAGE_DRIVER=s3
STORAGE_S3_REGION=us-east-1
STORAGE_S3_ENDPOINT=https://<minio-host>:9000
STORAGE_S3_KEY=<access-key>
STORAGE_S3_SECRET=<secret-key>
STORAGE_S3_BUCKET_SOURCE=task-source
STORAGE_S3_BUCKET_DOCUMENT=task-documents
STORAGE_S3_BUCKET_CHUNK=task-chunks
STORAGE_S3_BUCKET_AVATAR=task-avatars
```

Рекомендуется включить `versioning` и `lifecycle` на бакетах в консоли MinIO.

## Production: миграция local → MinIO/S3 (опционально)

Если на production раньше использовалось local‑хранилище, то для перехода нужен импорт (логическое копирование) файлов в бакеты.

В проекте есть консольная команда миграции: `app:file-storage:migrate-to-s3` (
`Console\Module\Source\Command\MigrateToFileSystemCommand`).  
Команду запускаем на production‑хосте от пользователя `wwwtask` из корня проекта (`/var/www/task`).

### Шаги

1) **Подготовьте MinIO**: установка, бакеты и доступ (см. разделы выше).  
   Имена бакетов должны совпадать с `STORAGE_S3_BUCKET_*`.

2) **Проверьте env на production‑хосте** (во время копирования `STORAGE_DRIVER` может оставаться `local`):

```dotenv
STORAGE_DRIVER=local        # во время копирования может оставаться local
STORAGE_S3_REGION=us-east-1
STORAGE_S3_ENDPOINT=https://<minio-host>:9000
STORAGE_S3_KEY=...
STORAGE_S3_SECRET=...
STORAGE_S3_BUCKET_SOURCE=task-source
STORAGE_S3_BUCKET_DOCUMENT=task-documents
STORAGE_S3_BUCKET_CHUNK=task-chunks
STORAGE_S3_BUCKET_AVATAR=task-avatars

SOURCE_STORAGE_DIR=/mnt/task/files/data-source
DOCUMENT_STORAGE_DIR=/mnt/task/files/documents
CHUNK_STORAGE_DIR=/mnt/task/files/chunks
AVATAR_STORAGE_DIR=/mnt/task/files/avatars
```

3) **Сначала прогоните DRY RUN** для каждого бакета (можно заранее, до остановки сервисов):

```bash
cd /var/www/task
sudo -u wwwtask php bin/console app:file-storage:migrate-to-s3 source --dry-run
sudo -u wwwtask php bin/console app:file-storage:migrate-to-s3 document --dry-run
sudo -u wwwtask php bin/console app:file-storage:migrate-to-s3 document_chunk --dry-run
sudo -u wwwtask php bin/console app:file-storage:migrate-to-s3 avatar --dry-run
```

Команда покажет статистику и список файлов к переносу. Если объём слишком большой для одного запуска — используйте
`--batch-size`.

4) **Остановите writer‑сервисы**, чтобы файлы не менялись во время переноса:

Остановите сервисы, которые пишут в local‑storage (web/API и Messenger consumers), вашим способом управления процессами
(systemd или Supervisor). Например:

- systemd: `sudo systemctl stop 'task-worker-*'`
- Supervisor: `sudo supervisorctl stop task-workers:*`

5) **Выполните реальную миграцию**:

```bash
cd /var/www/task
sudo -u wwwtask php bin/console app:file-storage:migrate-to-s3 source
sudo -u wwwtask php bin/console app:file-storage:migrate-to-s3 document
sudo -u wwwtask php bin/console app:file-storage:migrate-to-s3 document_chunk
sudo -u wwwtask php bin/console app:file-storage:migrate-to-s3 avatar
```

Опции:

- `--dry-run` — без копирования (для оценки).
- `--force` — перезаписать объекты в S3, если они уже есть.
- `--batch-size=100` — размер пакета для пауз/обновления прогресса.

6) **Переключите приложение на S3**:

```dotenv
STORAGE_DRIVER=s3
```

Задеплойте и запустите остановленные сервисы.

7) **Проверьте работу**:

- Загрузка новых Source/документов/аватаров идёт в MinIO.
- Старые артефакты читаются из MinIO.
- При необходимости прогоните `php bin/console app:file-storage:cleanup --mode=list` для контроля «осиротевших» файлов.

### Важные замечания

- На время миграции потребуется **двойной объём диска**: данные будут продублированы в MinIO.
- Если в local‑пути есть «запрещённые» для S3 символы (`? * < > | : " \\`), команда сохранит объект под хешированным
  именем. Такие случаи нужно проверить вручную, иначе приложение может не найти файл по старому ключу.
- Источники типа `github` требуют local‑storage (работа с директориями); в режиме `STORAGE_DRIVER=s3` они не поддержаны.

## Примечания

- `config/packages/storage.php` автоматически переключает драйвер на S3 при `STORAGE_DRIVER=s3` и использует
  `use_path_style_endpoint=true`, что требуется для MinIO.
- Для production задайте отдельные креды и включите политики lifecycle/versioning на бакетах.
