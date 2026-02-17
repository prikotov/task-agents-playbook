# Настройка выделенного сервера для воркеров TasK

Этот раздел содержит полную документацию по развертыванию сервера для обработки источников TasK без использования
контейнеризации.

## Оглавление

1. [Архитектура воркеров](#архитектура-воркеров) - обзор типов воркеров и очередей
2. [Быстрый старт](#быстрый-старт) - скрипт автоматической установки базовых компонентов
3. [Необходимое ПО](#необходимое-по) - базовое ПО и утилиты обработки
4. [Структура каталогов](#структура-каталогов) - рекомендуемая структура развертывания
5. [Установка и настройка окружения](#установка-и-настройка-окружения) - создание пользователя и установка компонентов
6. [Развертывание кода приложения](#2-развертывание-кода-приложения) - клонирование репозитория и установка зависимостей
7. [Настройка переменных окружения](#3-настройка-переменных-окружения) - конфигурация окружения приложения
8. [Утилиты обработки источников](#утилиты-обработки-источников) - установка и настройка специализированных утилит
9. [Подключение к RabbitMQ](#подключение-к-rabbitmq) - настройка соединения и очередей
10. [Настройка Supervisor-сервисов](#настройка-supervisor-сервисов-для-воркеров) - конфигурация воркеров через Supervisor
11. [Проверка работоспособности](#проверка-работоспособности) - тестирование настройки
12. [Мониторинг и обслуживание](#мониторинг-и-обслуживание) - логирование и обновления
13. [Масштабирование](#масштабирование) - рекомендации по производительности
14. [Безопасность](#безопасность) - рекомендации по защите
15. [Решение проблем](#решение-проблем) - диагностика и частые проблемы

## Архитектура воркеров

Воркеры TasK обрабатывают различные типы источников через очередь сообщений RabbitMQ:

- **source_download** - скачивание источников (YouTube, RuTube, веб-страницы)
- **source_extract** - извлечение метаданных
- **source_convert** - конвертация форматов (DjVu в PDF и другие)
- **source_diarize** - диаризация аудио (разделение по спикерам)
- **source_transcribe** - транскрибация аудио/видео
- **source_make_document** - создание документов из PDF/изображений
- **source_make_document_chunks** - подготовка чанков для RAG
- **source_events** - обработка событий

## Быстрый старт

Для быстрой настройки сервера используйте автоматический скрипт (только для AlmaLinux 10+):

```bash
# Скачайте и запустите скрипт
curl -O https://raw.githubusercontent.com/your-repo/task/main/docs/devops/production/setup-worker-production/quick-start.sh
chmod +x quick-start.sh
sudo ./quick-start.sh
```

После выполнения скрипта выполните ручную настройку согласно инструкциям ниже.

## Необходимое ПО

### Базовое ПО

- PHP 8.4 с расширениями (amqp, pdo, curl и др.)
- `ext-memcached` (>= 3.1.6) для Symfony Cache (даже если Memcached-сервер на отдельном хосте)
- Composer
- RabbitMQ сервер
- Системные утилиты (gcc, make, cmake, git, python3, ffmpeg)

### Утилиты обработки источников

- **yt-dlp** - для скачивания видео с YouTube/RuTube
- **whisper.cpp** - для транскрибации аудио/видео (поддерживает GPU ускорение)
- **diarize.py** - для диаризации (разделения по спикерам, требует GPU для оптимальной производительности)
- **pdfinfo** - для чтения метаданных PDF
- **ddjvu** - для конвертации DjVu в PDF
- **Trafilatura** - для извлечения текста с веб-страниц
- **Docling** - для конвертации PDF в Markdown
- **MinerU** - альтернативный конвертер PDF
- **Ollama** - для локальных LLM и эмбеддингов (поддерживает GPU ускорение)
- **NVIDIA CUDA Toolkit** - для GPU ускорения (опционально, но рекомендуется)

## Структура каталогов

Для развертывания воркеров используется основная директория проекта, где располагается код приложения, и системные
каталоги для утилит обработки.

```
/var/www/task/                                      # Основная директория проекта

# Утилиты устанавливаются в систему:
/opt/                                                # Основные утилиты
├── trafilatura/                                    # Извлечение текста с веб-страниц
├── docling/                                        # Конвертация PDF в Markdown
├── mineru/                                         # Альтернативный конвертер PDF
├── yt-dlp/                                         # Скачивание видео с YouTube/RuTube
├── whisper.cpp/                                    # Транскрибация аудио/видео
├── diarize/                                        # Окружение Python для диаризации
└── diarize-workdir/                                # Скрипты диаризации
    ├── diarize.py
    └── config.yaml
```

Особенности этой структуры:

- **Соответствие контейнеру**: Структура совпадает
  с [Docker-окружением](../../../../devops/docker/php/worker-cli.Dockerfile)

## Установка и настройка окружения

### 1. Создание пользователя и deploy key

Создайте пользователя `wwwtask` и подготовьте SSH deploy key по
инструкции [Создание пользователя wwwtask](../wwwtask-user-setup.md).

### 2. Базовые компоненты

#### Подключение репозиториев

```bash
# Для AlmaLinux 10
sudo dnf update
sudo dnf install epel-release
sudo dnf install https://download1.rpmfusion.org/free/el/rpmfusion-free-release-10.noarch.rpm
sudo dnf install https://rpms.remirepo.net/enterprise/remi-release-10.rpm
sudo dnf module reset php
sudo dnf module enable php:remi-8.4
```

#### PHP 8.4 с расширениями

```bash
# Для AlmaLinux 10
sudo dnf install \
    php-cli \
    php-json \
    php-mbstring \
    php-xml \
    php-pdo \
    php-pgsql \
    php-amqp \
    php-pecl-memcached \
    php-curl \
    php-zip \
    php-gd \
    php-intl \
    php-opcache \
    php-bcmath \
    php-process
```

#### Composer

```bash
# Установка глобально
#curl -sS https://getcomposer.org/installer | php
#sudo mv composer.phar /usr/local/bin/composer
#sudo chmod +x /usr/local/bin/composer
dnf install composer
```

#### Системные утилиты и библиотеки

```bash
# Для AlmaLinux 10
sudo dnf install \
    ca-certificates \
    cmake \
    curl \
    djvulibre \
    ffmpeg \
    gcc \
    gcc-c++ \
    git \
    make \
    poppler-utils \
    unzip \
    which \
    python3 \
    python3-devel \
    python3-pip \
    python3-setuptools \
    python3-virtualenv \
    supervisor
```

#### Postgresql

```bash
dnf install https://download.postgresql.org/pub/repos/yum/reporpms/EL-10-x86_64/pgdg-redhat-repo-latest.noarch.rpm
dnf install postgresql17
```

### 2. Развертывание кода приложения

```bash
# Создание основной директории проекта (если её ещё нет)
sudo install -d -m 755 -o wwwtask -g wwwtask /var/www/task

# SSH deploy key уже создан по инструкции из шага 1
sudo -u wwwtask git clone git@github.com:prikotov/TasK.git /var/www/task
cd /var/www/task

# Установка зависимостей
sudo -u wwwtask make install
```

### 3. Настройка переменных окружения

Создайте файл `.env.local` в `/var/www/task`:

```bash
sudo -u wwwtask touch /var/www/task/.env.local
sudo chmod 600 /var/www/task/.env.local
```

Пример содержимого `.env.local`:

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<your-secret-key>
DATABASE_URL=postgresql://user:password@db-host:5432/dbname
RABBITMQ_DSN=amqp://task:password@rabbitmq-host:5672/task
MEMCACHED_DSN=memcached://memcached-host:11211
MERCURE_WORKER_PUBLISH_URL=http://mercure-internal/.well-known/mercure
MERCURE_WORKER_PUBLISHER_JWT_SECRET=<worker-publisher-secret>
SOURCE_STATUS_TOPIC_BASE_URI=https://task.ai-aid.pro/source-status/users

# Пути к утилитам
YT_DLP_BIN_PATH=/usr/local/bin
WHISPER_CPP_UTILS_PATH=/opt/whisper.cpp
WHISPER_CPP_ASR_MODEL=ggml-large-v3.bin
PYANNOTE_AUDIO_UTILS_PATH=/opt/diarize-workdir/diarize.py
PYANNOTE_AUDIO_PYTHON_PATH=/opt/diarize/bin/python
PDFINFO_BIN_PATH=/usr/bin/pdfinfo
DJVU_BIN_PATH=/usr/bin/ddjvu
TRAFILATURA_BIN_PATH=/opt/trafilatura/bin/trafilatura
DOCLING_BIN_PATH=/opt/docling/bin/docling
MINERU_BIN_PATH=/opt/mineru/bin/mineru

# Токен HuggingFace для диаризации
PYANNOTE_HF_TOKEN=hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Подробная инструкция по развертыванию Memcached (сервер и настройка DSN): [../../memcached.md](../../memcached.md).

## Утилиты обработки источников

### 1. yt-dlp (для YouTube/RuTube)

[Подробная инструкция](yt-dlp.md)

### 2. whisper.cpp (транскрибация аудио)

[Подробная инструкция](whisper.md)

### 3. Диаризация (diarize.py)

[Подробная инструкция](diarize.md)

### 4. pdfinfo (Poppler)

[Подробная инструкция](pdfinfo.md)

### 5. DjVu (ddjvu)

[Подробная инструкция](djvu.md)

### 6. Trafilatura (извлечение текста с веб-страниц)

[Подробная инструкция](trafilatura.md)

### 7. Docling (конвертация PDF в Markdown)

[Подробная инструкция](docling.md)

### 8. MinerU (альтернативный конвертер PDF)

[Подробная инструкция](mineru.md)

### 9. Ollama (для LLM и эмбеддингов)

[Подробная инструкция](ollama.md)

## Подключение к RabbitMQ

Полная и актуальная инструкция по установке и настройке RabbitMQ, конфигурации vhost/пользователей и созданию очередей находится в общем документе
[RabbitMQ](../rabbitmq.md). Используйте его как основной источник истины, а в этом разделе ориентируйтесь только на общую архитектуру воркеров и очередей.

## Настройка Supervisor-сервисов для воркеров

Для управления воркерами в production рекомендуется использовать Supervisor, который обеспечивает гибкое управление
процессами и их автоматический перезапуск.

### 1. Создание конфигурации Supervisor

Подробные инструкции по настройке Supervisor приведены в [supervisor-services.md](supervisor-services.md).
Шаблон конфигурации включает программы для всех очередей: source_download, source_extract, source_convert,
source_diarize,
source_transcribe, source_make_document, source_make_document_chunks, source_events.
Для AlmaLinux: основной файл `/etc/supervisord.conf`, include — `/etc/supervisord.d/*.ini`.

Краткая инструкция:

```bash
# Создание конфигурационного файла
sudo nano /etc/supervisord.d/task-workers.ini

# Создание директорий для логов
sudo mkdir -p /var/log/task/messenger

# Настройка прав доступа
sudo chown -R wwwtask:wwwtask /var/log/task/messenger

# Перезагрузка конфигурации Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Запуск всех воркеров
sudo supervisorctl start task-workers:*

# Проверка статуса
sudo supervisorctl status
```

### 2. Автоматическая настройка

Для удобства можно использовать автоматический скрипт настройки:

```bash
# Запуск автоматической настройки
sudo /usr/local/bin/setup-task-supervisor.sh
```

Этот скрипт создаст все необходимые конфигурационные файлы и директории, а также настроит правильные права доступа.

### 3. Управление воркерами

```bash
# Запуск всех воркеров
sudo supervisorctl start task-workers:*

# Остановка всех воркеров
sudo supervisorctl stop task-workers:*

# Перезапуск всех воркеров
sudo supervisorctl restart task-workers:*

# Просмотр логов в реальном времени
sudo supervisorctl tail -f source_download

# Проверка статуса
sudo supervisorctl status
```

## Проверка работоспособности

### 0. Проверка PHP расширения Memcached

```bash
sudo -u wwwtask php --ri memcached
sudo -u wwwtask php -r 'echo "memcached ext version: ".phpversion("memcached").PHP_EOL;'
```

### 1. Проверка подключения к RabbitMQ

```bash
# От имени пользователя wwwtask
sudo -u wwwtask php /var/www/task/bin/console messenger:consume source_download --env=prod --no-interaction --time-limit=1
```

### 2. Проверка утилит

Для проверки работы каждой утилиты следуйте соответствующим подробным инструкциям:

- [yt-dlp](yt-dlp.md)
- [whisper.cpp](whisper.md)
- [Диаризация](diarize.md)
- [pdfinfo](pdfinfo.md)
- [DjVu](djvu.md)
- [Trafilatura](trafilatura.md)
- [Docling](docling.md)
- [MinerU](mineru.md)
- [Ollama](ollama.md)

### 3. Проверка обработки тестового задания

```bash
# Создание тестового источника через API или веб-интерфейс
# и отслеживание его обработки в логах:
sudo tail -f /var/log/task/messenger/*.log
```

## Мониторинг и обслуживание

### 1. Логирование

- Основные логи воркеров: `/var/log/task/messenger/*.log`
- Логи ошибок: `/var/log/task/messenger/*.error.log`
- Логи приложения: `/var/www/task/var/log/prod.log`

### 2. Мониторинг очередей

```bash
# Проверка состояния очередей
sudo -u wwwtask php /var/www/task/bin/console messenger:stats

# Проверка неудачных сообщений
sudo -u wwwtask php /var/www/task/bin/console messenger:failed:show
```

### 3. Обновление утилит

Регулярно обновляйте утилиты для обеспечения безопасности и производительности:

```bash
# Обновление yt-dlp
sudo /opt/yt-dlp/bin/pip install --upgrade yt-dlp

# Обновление Python-пакетов
sudo /opt/trafilatura/bin/pip install --upgrade trafilatura
sudo /opt/docling/bin/pip install --upgrade docling
sudo /opt/mineru/bin/pip install --upgrade mineru
```

## Масштабирование

Для увеличения производительности можно запустить несколько экземпляров воркеров:

```bash
# Запуск нескольких экземпляров source_transcribe через Supervisor
# Добавьте в конфигурацию дополнительные программы source_transcribe_2, source_transcribe_3
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start source_transcribe_2 source_transcribe_3
```

Рекомендации по масштабированию:

- **source_download**: 1-2 воркера
- **source_extract**: 1-2 воркера
- **source_convert**: 1-2 воркера
- **source_diarize**: 1-2 воркера (требует много CPU/RAM, значительно ускоряется с GPU)
- **source_transcribe**: 2-4 воркера (требует много CPU/RAM, значительно ускоряется с GPU)
- **source_make_document**: 2-4 воркера (требует много CPU/RAM)
- **source_make_document_chunks**: 1-2 воркера
- **source_events**: 1 воркер

Примечание о GPU:

- При наличии NVIDIA GPU с 6+ ГБ VRAM производительность source_transcribe и source_diarize увеличивается в 3-10 раз
- Рекомендуется уменьшить количество воркеров CPU при использовании GPU и увеличить количество обрабатываемых задач на
  одного воркера

## Безопасность

1. Ограничьте доступ к RabbitMQ только с доверенных хостов
2. Используйте сложные пароли для всех сервисов
3. Регулярно обновляйте системные пакеты
4. Ограничьте права пользователя `wwwtask` только необходимыми операциями
5. Настройте ротацию логов

## Решение проблем

### Частые проблемы

1. **Воркер не запускается**
    - Проверьте права доступа к файлам
    - Убедитесь, что все переменные окружения заданы корректно
    - Проверьте логи Supervisor: `sudo supervisorctl tail -f source_download`

2. **Ошибка `Memcached > 3.1.5 is required.`**
    - На сервере не установлен `ext-memcached` или он слишком старый
    - Проверьте: `sudo -u wwwtask php --ri memcached`
    - Установите/обновите: `sudo dnf install -y php-pecl-memcached`

3. **Очереди не обрабатываются**
    - Проверьте подключение к RabbitMQ
    - Проверьте наличие сообщений в очередях

4. **Утилиты не работают**
    - Проверьте права доступа к бинарным файлам
    - Убедитесь, что все зависимости установлены
    - Проверьте переменные окружения

### Диагностика

```bash
# Проверка статуса всех воркеров
sudo supervisorctl status

# Проверка логов конкретного воркера
sudo supervisorctl tail -f source_download

# Проверка соединения с RabbitMQ
sudo -u wwwtask php /var/www/task/bin/console debug:config messenger
```

## Полезные ссылки

- [Документация RabbitMQ](https://www.rabbitmq.com/documentation.html)
- [Документация yt-dlp](https://github.com/yt-dlp/yt-dlp)
- [Документация whisper.cpp](https://github.com/ggerganov/whisper.cpp)
- [Документация Docling](https://docling-project.github.io/docling/)
- [Документация MinerU](https://opendatalab.github.io/MinerU/)
- [Документация Ollama](https://ollama.com/documentation)
- [Документация Supervisor](http://supervisord.org/)
- [Supervisor конфигурация](supervisor-services.md) - подробная настройка Supervisor для воркеров
- [worker-cli.Dockerfile](../../../../devops/docker/php/worker-cli.Dockerfile) - референс для контейнеризированного
  развертывания
- [compose.yaml](../../../../compose.yaml) - конфигурация Docker Compose

## Поддержка

При возникновении проблем:

1. Проверьте [контрольный список](checklist.md)
2. Изучите логи ошибок
3. Обратитесь к основной инструкции выше
4. Создайте issue в репозитории проекта с подробным описанием проблемы

## Заключение

После выполнения всех шагов у вас будет настроен выделенный сервер для обработки источников TasK. Регулярно проверяйте
состояние воркеров, обновляйте ПО и следите за свободным местом на диске.
