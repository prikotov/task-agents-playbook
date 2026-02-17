# Whisper.cpp в контейнере

Whisper.cpp можно запускать без локальной установки зависимостей, используя официальные Docker-образы. Такой подход
идентичен будущему микросервису транскрипции: основной Symfony-контур остаётся лёгким, а тяжёлые модели и бинарники
живут в отдельном контейнере.

## Образы

| Образ | Назначение |
|-------|------------|
| `ghcr.io/ggml-org/whisper.cpp:main` | CPU-исполнение, содержит `whisper-cli`, `curl`, `ffmpeg`. |
| `ghcr.io/ggml-org/whisper.cpp:main-cuda` | То же, но собранное с поддержкой CUDA/cuBLAS. |
| `ghcr.io/ggml-org/whisper.cpp:main-musa` | Вариант под Moore Threads (MUSA). |

> Следите за версиями в [README проекта](https://github.com/ggml-org/whisper.cpp#docker). Для продакшена фиксируйте тег
> образа и публикуйте собственный `speech-tools` образ, если нужны доп. скрипты.

## Быстрый старт

Создайте каталоги для моделей и артефактов:

```bash
mkdir -p /opt/task/speech-tools/models
mkdir -p /opt/task/speech-tools/audios
```

Скачайте модель (persist через volume):

```bash
docker run --rm -it \
  -v /opt/task/speech-tools/models:/models \
  ghcr.io/ggml-org/whisper.cpp:main \
  "./models/download-ggml-model.sh base /models"
```

Запустите транскрипцию:

```bash
docker run --rm -it \
  -v /opt/task/speech-tools/models:/models \
  -v /opt/task/speech-tools/audios:/audios \
  ghcr.io/ggml-org/whisper.cpp:main \
  "whisper-cli -m /models/ggml-base.bin -f /audios/jfk.wav -otxt"
```

Для GPU замените образ на `ghcr.io/ggml-org/whisper.cpp:main-cuda` и прокиньте нужные устройства (`--gpus=all`).

## Пример docker-compose сервиса

Добавьте профиль `speech` к нужным сервисам (или укажите его при запуске), чтобы тяжёлый сервис поднимался только при необходимости:

```yaml
services:
  speech-tools:
    image: ghcr.io/ggml-org/whisper.cpp:main
    command: >
      sh -c "whisper-cli -m ${WHISPER_MODEL_PATH} -f ${WHISPER_INPUT_FILE} ${WHISPER_RUN_ARGS}"
    working_dir: /work
    profiles: ["speech"]
    volumes:
      - ./var/speech/models:/models
      - ./var/speech/work:/work
      - ./var/storage:/storage:ro   # входные файлы из общего стора
    environment:
      WHISPER_MODEL_PATH: "/models/ggml-large-v3.bin"
      WHISPER_RUN_ARGS: "--best-of 5 --beam-size 5"
```

Такой контейнер можно использовать как:

1. Одноразовую команду (`docker compose run speech-tools ...`) для локальной отладки.
2. Постоянный воркер/микросервис, который слушает очередь (start script читает задание из RabbitMQ и запускает
   `whisper-cli` для каждого файла).

## Интеграция с Source/TranscriberService

1. Symfony ставит задачу на транскрипцию в очередь `source_transcribe`.
2. Отдельный воркер на базе образа `whisper.cpp` забирает задачу, скачивает/монтирует артефакты и запускает CLI.
3. Результаты (JSON/SRT/текст) и лог сохраняются в общий storage (`Keeper`, S3, shared volume).
4. Воркера уведомляет Source (webhook или сообщение в `source_events`) о завершении.

В таком сценарии основной `web`/`worker` контейнер не содержит Python/CUDA зависимостей, а модели скачиваются один раз
в volume (`var/speech/models`).

## Окружение

Минимальные переменные, которые нужно зафиксировать в `.env.local`/secrets:

```dotenv
WHISPER_DOCKER_IMAGE=ghcr.io/ggml-org/whisper.cpp:main
WHISPER_DOCKER_PROFILE=speech
WHISPER_MODEL_KEY=ggml-large-v3.bin
WHISPER_SHARED_VOLUME=var/speech/models
```

При обновлении модели:

1. `docker pull $WHISPER_DOCKER_IMAGE`.
2. `docker compose run --rm speech-tools "./models/download-ggml-model.sh large-v3 /models"`.
3. Обновите переменные приложения (например, `WHISPER_CPP_ASR_MODEL=ggml-large-v3.bin`) и перезапустите воркеры.

## Диаризация

`whisper.cpp` поддерживает tinydiarize (`-tdrz`). Для более сложной диаризации (pyannote) продолжайте использовать
отдельный Python-образ/сервис, монтируя общий storage. Обе утилиты можно запускать параллельно в профиле `speech`.

## Ссылки

- Whisper.cpp README: https://github.com/ggml-org/whisper.cpp#docker
- Инструкции по локальной установке (legacy): [whisper.md](whisper.md)
