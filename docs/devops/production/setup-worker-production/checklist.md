# Контрольный список настройки сервера воркеров TasK

Этот контрольный список поможет убедиться, что все компоненты настроены корректно перед запуском воркеров в продакшене.

## Подготовка системы

- [ ] Установлена ОС AlmaLinux 10+
- [ ] Настроен доступ в интернет для скачивания зависимостей
- [ ] Настроен доступ к базе данных PostgreSQL
- [ ] Настроен доступ к RabbitMQ на отдельном сервере
- [ ] Настроен доступ к файловому хранилищу (MinIO/S3)
- [ ] Настроены права пользователя `wwwtask`
- [ ] Настроен файрвол (открыты порты для подключения к RabbitMQ)

## Установка ПО

### Базовое ПО
- [ ] Установлен PHP 8.4 с необходимыми расширениями
  - [ ] php84-cli
  - [ ] php84-json
  - [ ] php84-mbstring
  - [ ] php84-xml
  - [ ] php84-pdo
  - [ ] php84-pgsql
  - [ ] php84-amqp
  - [ ] php84-curl
  - [ ] php84-zip
  - [ ] php84-gd
  - [ ] php84-intl
  - [ ] php84-opcache
  - [ ] php84-bcmath
  - [ ] php84-process
- [ ] Установлен Composer
- [ ] Установлены системные утилиты
  - [ ] gcc, gcc-c++, make, cmake
  - [ ] git
  - [ ] python3, python3-devel, python3-pip, python3-setuptools, python3-virtualenv
  - [ ] ffmpeg
  - [ ] djvulibre (для DjVu)
  - [ ] poppler-utils (для pdfinfo)
  - [ ] postgresql (клиент)
  - [ ] supervisor (для управления воркерами)

### Подключение к RabbitMQ
- [ ] Проверена доступность сервера RabbitMQ
- [ ] Создан vhost `task` на сервере RabbitMQ
- [ ] Создан пользователь `task` на сервере RabbitMQ
- [ ] Настроены права пользователя
- [ ] Открыты порты в файрволе между серверами

### Утилиты обработки
- [ ] Установлен yt-dlp
  - [ ] Скачан бинарник
  - [ ] Настроены права доступа
  - [ ] Проверена работа
- [ ] Установлен whisper.cpp
  - [ ] Склонирован репозиторий
  - [ ] Собраны бинарники
  - [ ] Скачана модель large-v3
  - [ ] Проверена работа
- [ ] Установлена диаризация (diarize.py)
  - [ ] Создано виртуальное окружение в /opt/diarize
  - [ ] Установлены зависимости (huggingface_hub, torch, pyannote-audio, soundfile, speechbrain, torchaudio, requests)
  - [ ] Создана рабочая директория /opt/diarize-workdir
  - [ ] Скопирован скрипт diarize.py в /opt/diarize-workdir/
  - [ ] Скопирован config.yaml в /opt/diarize-workdir/
  - [ ] Создан wrapper-скрипт /opt/diarize-workdir/diarize.sh
  - [ ] Созданы символические ссылки в /usr/local/bin/ (diarize, diarize.py)
  - [ ] Обновлен shebang в diarize.py на #!/opt/diarize/bin/python
  - [ ] Настроен токен HuggingFace
- [ ] Установлен pdfinfo (Poppler)
  - [ ] Проверена работа
- [ ] Установлен ddjvu (DjVuLibre)
  - [ ] Проверена работа
- [ ] Установлена Trafilatura
  - [ ] Создано виртуальное окружение
  - [ ] Проверена работа
- [ ] Установлен Docling
  - [ ] Создано виртуальное окружение
  - [ ] Проверена работа
- [ ] Установлен MinerU
  - [ ] Создано виртуальное окружение
  - [ ] Проверена работа
- [ ] Установлен Ollama (опционально, только если требуется локальная обработка LLM)
  - [ ] Настроен автозапуск
  - [ ] Скачаны модели:
    - [ ] jeffh/intfloat-multilingual-e5-large-instruct:q8_0
    - [ ] bge-m3
    - [ ] qwen3:4b

## Настройка приложения

### Развертывание кода
- [ ] Создана структура каталогов в `/var/www/task`
  - [ ] `/var/www/task/releases`
  - [ ] `/var/www/task/shared/var`
  - [ ] `/var/www/task/env`
- [ ] Проверена установка утилит в `/opt/`
  - [ ] `/opt/trafilatura`
  - [ ] `/opt/docling`
  - [ ] `/opt/mineru`
  - [ ] `/opt/yt-dlp`
- [ ] `/opt/whisper.cpp`
- [ ] `/opt/diarize`
- [ ] `/opt/diarize-workdir`
- [ ] Создана новая версия приложения в `/var/www/task/releases/task-YYYY-MM-DD-vX.X.X`
- [ ] Репозиторий TasK развернут в `/var/www/task`
- [ ] Установлены зависимости Composer
- [ ] Настроены права доступа к файлам
- [ ] Созданы необходимые директории
  - [ ] `/var/www/task/shared/var`
  - [ ] `/var/www/task/shared/var/log`
  - [ ] `/var/log/task/messenger`

### Конфигурация
- [ ] В корне проекта настроен `.env.local` (или `.env.prod.local`) для worker server
- [ ] Настроены переменные окружения
  - [ ] `APP_ENV=prod`
  - [ ] `APP_DEBUG=0`
  - [ ] `APP_SECRET`
  - [ ] `DATABASE_URL`
  - [ ] `RABBITMQ_DSN`
  - [ ] Настройки S3 хранилища
    - [ ] `STORAGE_DRIVER=s3`
    - [ ] `STORAGE_S3_REGION`
    - [ ] `STORAGE_S3_ENDPOINT`
    - [ ] `STORAGE_S3_KEY`
    - [ ] `STORAGE_S3_SECRET`
    - [ ] `STORAGE_S3_BUCKET_SOURCE`
    - [ ] `STORAGE_S3_BUCKET_DOCUMENT`
    - [ ] `STORAGE_S3_BUCKET_CHUNK`
    - [ ] `STORAGE_S3_BUCKET_AVATAR`
  - [ ] Пути к утилитам
    - [ ] `FFMPEG_UTIL_PATH=/usr/bin/ffmpeg`
    - [ ] `FFPROBE_UTIL_PATH=/usr/bin/ffprobe`
    - [ ] `YT_DLP_BIN_PATH=/usr/local/bin`
    - [ ] `WHISPER_CPP_UTILS_PATH=/opt/whisper.cpp`
    - [ ] `WHISPER_CPP_ASR_MODEL=ggml-large-v3.bin`
    - [ ] `WHISPER_CPP_THREADS=8`
    - [ ] `WHISPER_CPP_HOST=127.0.0.1`
    - [ ] `WHISPER_CPP_RUN_PARAMS="--best-of 5 --beam-size 5 --max-context 64 --split-on-word --max-len 120"`
    - [ ] `PYANNOTE_AUDIO_UTILS_PATH=/opt/diarize-workdir/diarize.py`
    - [ ] `PYANNOTE_AUDIO_PYTHON_PATH=/opt/diarize/bin/python`
    - [ ] `PYANNOTE_AUDIO_THREADS=8`
    - [ ] `PYANNOTE_PROXY=`
    - [ ] `PYANNOTE_HF_TOKEN`
    - [ ] `PDFINFO_BIN_PATH=/usr/bin/pdfinfo`
    - [ ] `DJVU_BIN_PATH=/usr/bin/ddjvu`
    - [ ] `TRAFILATURA_BIN_PATH=/opt/trafilatura/bin/trafilatura`
    - [ ] `DOCLING_BIN_PATH=/opt/docling/bin/docling`
    - [ ] `MINERU_BIN_PATH=/opt/mineru/bin/mineru`
  - [ ] Source workspace cache (см. [source-workspace-cache.md](source-workspace-cache.md))
    - [ ] `SOURCE_FILE_WORKSPACE_MODE=cache`
    - [ ] `SOURCE_FILE_WORKSPACE_CACHE_DIR=/var/cache/task/source-workspace` (prod override)
    - [ ] `SOURCE_FILE_WORKSPACE_CACHE_TTL_SECONDS=604800`
    - [ ] `SOURCE_FILE_WORKSPACE_CACHE_MAX_BYTES=53687091200`
  - [ ] Опциональные настройки Mercure
    - [ ] `MERCURE_URL`
    - [ ] `MERCURE_PUBLIC_URL`
    - [ ] `NOTIFICATION_TOPIC_BASE_URI`
- [ ] Проверена конфигурация приложения
  - [ ] Подключение к базе данных
  - [ ] Подключение к RabbitMQ
  - [ ] Доступ к утилитам

## Настройка сервисов воркеров

### Выбор способа управления
- [ ] Выбран способ управления воркерами:
  - [ ] Supervisor (рекомендуется)

### Настройка через Supervisor (рекомендуется)
- [ ] Создан файл конфигурации `/etc/supervisord.d/task-workers.ini`
- [ ] Настроены программы для всех типов воркеров
- [ ] Созданы необходимые директории для логов
- [ ] Настроены права доступа к директориям
- [ ] Перезагружена конфигурация Supervisor
- [ ] Запущены воркеры через Supervisor
- [ ] Проверен статус воркеров

### Проверка сервисов
- [ ] Запущены все сервисы
- [ ] Проверен статус всех сервисов
- [ ] Проверены логи на отсутствие ошибок
- [ ] Проверено подключение к RabbitMQ
- [ ] Настроен регулярный prune для workspace cache (cron/systemd timer)

## Тестирование

### Функциональное тестирование
- [ ] Создан тестовый источник через API
- [ ] Проверена обработка на каждом этапе
  - [ ] source_download
  - [ ] source_extract
  - [ ] source_transcribe (если применимо)
  - [ ] source_make_document
  - [ ] source_make_document_chunks
- [ ] Проверены логи обработки
- [ ] Проверен результат обработки

### Нагрузочное тестирование
- [ ] Создано несколько тестовых источников
- [ ] Проверена параллельная обработка
- [ ] Проверена работа при высокой нагрузке
- [ ] Проверена стабильность системы

## Мониторинг и логирование

### Логирование
- [ ] Настроена ротация логов
- [ ] Настроено централизованное логирование (если требуется)
- [ ] Проверены пути к логам
  - [ ] `/var/log/task/messenger/*.log`
  - [ ] `/var/log/task/messenger/*.error.log`
- [ ] `/var/www/task/var/log/prod.log`

### Мониторинг
- [ ] Настроен мониторинг системных ресурсов
  - [ ] CPU
  - [ ] RAM
  - [ ] Дисковое пространство
- [ ] Настроен мониторинг RabbitMQ
  - [ ] Статус очередей
  - [ ] Количество сообщений
  - [ ] Количество потребителей
- [ ] Настроен мониторинг сервисов Supervisor
- [ ] Настроены алерты при сбоях

## Безопасность

### Общая безопасность
- [ ] Настроен файрвол
- [ ] Ограничен доступ к RabbitMQ
- [ ] Используются сложные пароли
- [ ] Регулярно обновляются системные пакеты
- [ ] Настроен доступ по SSH только по ключам

### Безопасность приложения
- [ ] Ограничены права пользователя `wwwtask`
- [ ] Настроены права доступа к файлам
- [ ] Секретные данные хранятся в защищенном месте
- [ ] Отключен отладочный режим в продакшене

## Резервное копирование

### Бэкап конфигурации
- [ ] Настроен бэкап конфигурации Supervisor сервисов
- [ ] Настроен бэкап конфигурации Supervisor сервисов
- [ ] Настроен бэкап конфигурации RabbitMQ

### Бэкап данных
- [ ] Настроен бэкап базы данных
- [ ] Настроен бэкап файлового хранилища
- [ ] Проверено восстановление из бэкапа

## Документация

### Внутренняя документация
- [ ] Документированы IP-адреса серверов
- [ ] Документированы учетные данные
- [ ] Документированы процедуры восстановления
- [ ] Документированы процедуры обновления

### Операционная документация
- [ ] Созданы инструкции для операторов
- [ ] Созданы инструкции для устранения проблем
- [ ] Описаны процедуры мониторинга
- [ ] Описаны процедуры масштабирования

## Финальная проверка

- [ ] Все воркеры запущены и работают стабильно
- [ ] Обработка источников работает корректно
- [ ] Мониторинг показывает нормальные показатели
- [ ] Логи не содержат критических ошибок
- [ ] Система готова к работе в продакшене

## Регулярное обслуживание

### Еженедельно
- [ ] Проверка логов на наличие ошибок
- [ ] Проверка свободного места на диске
- [ ] Проверка обновлений системных пакетов
- [ ] Проверка состояния очередей RabbitMQ

### Ежемесячно
- [ ] Обновление утилит обработки
  - [ ] yt-dlp
  - [ ] Python-пакеты (trafilatura, docling, mineru)
  - [ ] Ollama модели
- [ ] Проверка бэкапов
- [ ] Анализ производительности системы

### По необходимости
- [ ] Обновление whisper.cpp при выходе новых версий
- [ ] Обновление моделей распознавания
- [ ] Настройка дополнительных воркеров при увеличении нагрузки
- [ ] Оптимизация параметров производительности
