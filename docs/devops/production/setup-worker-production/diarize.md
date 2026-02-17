# Установка и настройка diarize.py

# TODO
- История с копированием скрипта diarize.py трудно поддается обновлению. Продумать решение
- /opt/diarize-workdir/diarize.sh - зачем?

`diarize.py` - это скрипт для диаризации аудио (разделения по спикерам), использующий библиотеку pyannote.audio. Скрипт
находится в репозитории проекта по пути `bin/src/diarize.py`.

## Требования

- Python 3.10+
- pyannote.audio
- torch
- speechbrain
- torchaudio
- soundfile
- Токен HuggingFace с доступом к моделям pyannote

## Установка

### 1. Создание виртуального окружения

```bash
# Создание виртуального окружения для диаризации
python3 -m venv /opt/diarize
/opt/diarize/bin/pip install --upgrade pip

# Установка PyTorch с CUDA (GPU).
# Важно: версия CUDA в `nvidia-smi` (например, 13.1) — это версия, поддерживаемая драйвером,
# а PyTorch ставится с конкретной версией CUDA runtime (например, cu128).
/opt/diarize/bin/pip install --no-cache-dir --index-url https://download.pytorch.org/whl/cu128 \
  "torch==2.8.0" "torchaudio==2.8.0"

# Остальные зависимости
/opt/diarize/bin/pip install --no-cache-dir \
  "huggingface_hub==0.35.3" pyannote-audio soundfile speechbrain "requests[socks]"
```

### 2. Подключение скрипта и конфигурации

Рекомендуемый вариант — использовать `diarize.py` и `config.yaml` прямо из проекта TasK в `/var/www/task`, чтобы они
обновлялись вместе с деплоем.

Проверьте, что файлы существуют и доступны пользователю `wwwtask`:

```bash
ls -la /var/www/task/bin/src/diarize.py /var/www/task/bin/src/config.yaml
```

Опционально (для удобного ручного запуска): создайте wrapper `/usr/local/bin/diarize`, который всегда запускает скрипт
из проекта и выставляет корректный `cwd` для поиска `config.yaml`:

```bash
sudo tee /usr/local/bin/diarize >/dev/null <<'SH'
#!/bin/bash
set -euo pipefail
cd /var/www/task/bin/src
exec /opt/diarize/bin/python /var/www/task/bin/src/diarize.py "$@"
SH
sudo chmod +x /usr/local/bin/diarize
```

### 3. Настройка переменных окружения

В production воркеры запускаются через Supervisor, но переменные окружения хранятся в `.env*` файлах в корне проекта и подтягиваются Symfony при запуске `bin/console`.

Добавьте переменные в `.env.local` (или `.env.prod.local`) в корне проекта на worker server:

```dotenv
# Путь к скрипту diarize.py
PYANNOTE_AUDIO_UTILS_PATH=/var/www/task/bin/src/diarize.py
PYANNOTE_AUDIO_PYTHON_PATH=/opt/diarize/bin/python

# Токен HuggingFace для доступа к моделям pyannote
PYANNOTE_HF_TOKEN=hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Количество потоков для обработки
PYANNOTE_AUDIO_THREADS=8

# (Опционально) Явный выбор device для pyannote pipeline:
# - auto (по умолчанию): cuda если доступно, иначе cpu
# - cpu: принудительно CPU
# - cuda / cuda:0: принудительно GPU
# PYANNOTE_DEVICE=cuda
```

Переменную `PYANNOTE_CACHE` рекомендуется задавать на уровне Supervisor только для воркера `source_diarize` (так как pyannote используется только на этом шаге). Шаблон конфигурации см. в [supervisor-services.md](supervisor-services.md).

Убедитесь, что в конфиге Supervisor для воркеров указан `directory` на каталог проекта, где лежат `.env*` файлы (см. [supervisor-services.md](supervisor-services.md)).

Если нужно включать GPU только для одного воркера, задайте `PYANNOTE_DEVICE` в секции `[program:source_diarize]` через
`environment=` (аналогично примеру с `PYANNOTE_CACHE`).

## Использование

### Запуск из командной строки

```bash
# Базовый запуск
diarize /path/to/audio.wav

# С указанием количества потоков
diarize -t 4 /path/to/audio.wav

# Или прямой вызов через Python
/opt/diarize/bin/python /var/www/task/bin/src/diarize.py /path/to/audio.wav
```

### Результат работы

Скрипт создает JSON-файл с результатами диаризации рядом с исходным аудиофайлом:

```json
[
    {
        "start": 0.0,
        "end": 2.5,
        "speaker": "SPEAKER_00"
    },
    {
        "start": 2.5,
        "end": 5.2,
        "speaker": "SPEAKER_01"
    }
]
```

## Совместимость с PyTorch 2.6+

В скрипте `diarize.py` добавлено исправление для совместимости с PyTorch 2.6+:

**Проблема:** В PyTorch 2.6+ изменилось значение по умолчанию параметра `weights_only` в `torch.load()` с `False` на
`True`.

**Решение:** Скрипт использует monkey patch для временного установки `weights_only=False` при загрузке моделей
pyannote.audio.

## Обновление

### Обновление зависимостей

```bash
# Обновление pip
/opt/diarize/bin/pip install --upgrade pip

# Обновление пакетов
/opt/diarize/bin/pip install --upgrade \
    speechbrain torchaudio torch pyannote-audio soundfile "requests[socks]"
```

### Обновление скрипта

```bash
# При рекомендуемой схеме (запуск из `/var/www/task/bin/src`) обновление происходит вместе с деплоем проекта.
# Если вы используете кастомную копию в `/opt/diarize-workdir`, обновите её вручную по аналогии.
```

## Диагностика

### Проверка установки

```bash
# Проверка версии Python
/opt/diarize/bin/python --version

# Проверка доступности GPU в окружении /opt/diarize
/opt/diarize/bin/python -c "import torch; print(torch.__version__); print('torch.version.cuda', torch.version.cuda); print('cuda.is_available', torch.cuda.is_available()); print('device0', torch.cuda.get_device_name(0) if torch.cuda.is_available() else None)"

# Проверка установленных пакетов
/opt/diarize/bin/pip list | grep -E "(torch|pyannote|speechbrain)"

# Проверка символических ссылок
ls -la /usr/local/bin/diarize*
ls -la /usr/local/bin/diarize.py

# Проверка токена
sudo bash -c 'echo $PYANNOTE_HF_TOKEN'
```

### Тестовый запуск

```bash
# Создание тестового аудиофайла (если нужно)
# Запуск с тестовым файлом
diarize /path/to/test_audio.wav
```

### Проверка, что GPU реально используется

В одном окне запустите тест:

```bash
/opt/diarize/bin/python - <<'PY'
import torch, time
print('cuda.is_available', torch.cuda.is_available())
print('torch.version.cuda', torch.version.cuda)
if not torch.cuda.is_available():
    raise SystemExit(2)
x = torch.randn((8192,8192), device='cuda')
y = x @ x
torch.cuda.synchronize()
print('ok', y.mean().item(), 'gpu', torch.cuda.get_device_name(0))
time.sleep(30)
PY
```

Параллельно выполните `nvidia-smi` — должен появиться процесс `/opt/diarize/bin/python` с ненулевым `GPU Memory Usage`.

### Частые проблемы

1. **Ошибка токена**
   ```
   RuntimeError: Pyannote token is not configured
   ```
   **Решение:** Проверьте переменную окружения `PYANNOTE_HF_TOKEN`

2. **Ошибка загрузки модели**
   ```
   FileNotFoundError: Configuration file not found: config.yaml
   ```
   **Решение:** Убедитесь, что `config.yaml` находится в той же директории, что и `diarize.py`

3. **Ошибка памяти**
   ```
   RuntimeError: CUDA out of memory
   ```
   **Решение:** Уменьшите `embedding_batch_size` и `segmentation_batch_size` в `config.yaml`

## Интеграция с TasK

В приложении TasK скрипт используется через компонент `PyannoteAudioComponent`. Путь к скрипту задается через переменную
окружения `PYANNOTE_AUDIO_UTILS_PATH`.

Пример использования в коде:

```php
$diarizeBinPath = $_ENV['PYANNOTE_AUDIO_UTILS_PATH'] ?? null;
if (!$diarizeBinPath) {
    throw new \RuntimeException('PYANNOTE_AUDIO_UTILS_PATH not configured');
}
```

## Производительность

### Рекомендации по оптимизации

1. **Количество потоков:** Установите равным половине количества CPU ядер
2. **Размер батчей:** Уменьшите при нехватке памяти
3. **Порог кластеризации:** Увеличьте для лучшего разделения спикеров
4. **GPU:** При наличии GPU установите `torch` с CUDA-поддержкой

### Пример конфигурации для слабых систем

```yaml
params:
  clustering:
    method: centroid
    min_cluster_size: 10
    threshold: 0.8
  segmentation:
    min_duration_off: 0.5

pipeline:
  params:
    embedding_batch_size: 1
    segmentation_batch_size: 8
