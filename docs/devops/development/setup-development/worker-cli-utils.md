# Инструкция по внедрению утилит в контейнер worker-cli

## Что было добавлено

В контейнер `worker-cli` были внедрены следующие компоненты:

### 1. yt-dlp
- Установлен через pip в изолированном окружении `/opt/yt-dlp`
- Доступен как `yt-dlp` через символическую ссылку в `/usr/local/bin/`
- Переменные окружения для PHP-кода загружаются из `.env` файлов Symfony в runtime

### 2. whisper.cpp (latest release)
- Скомпилирован из исходников в `/opt/whisper.cpp`
- Автоматически определяется и устанавливается последняя стабильная версия
- Переменные окружения для PHP-кода загружаются из `.env` файлов Symfony в runtime

### 3. diarize.py с конфигом
- Скопирован в `/opt/diarize-workdir/`
- Python окружение с зависимостями в `/opt/diarize`
- Версии зависимостей:
  - `huggingface_hub>=0.25.0` (для совместимости с pyannote.audio 4.x)
  - `pyannote-audio` 4.x (последняя версия)
  - `torch` (последняя версия для CPU)
  - `speechbrain`, `torchaudio`, `soundfile`
- Созданы обертки для удобного запуска:
  - `diarize` → bash wrapper
  - `diarize.py` → Python script с правильным shebang
- Переменные окружения для PHP-кода загружаются из `.env` файлов Symfony в runtime

## Сборка контейнера

```bash
# Собрать контейнер
make build-worker-cli

# Запустить контейнер
make worker-cli-up

# Проверить логи
docker logs task-worker-cli-1
```

## Тестирование функциональности

Для тестирования можно использовать скрипт `test-worker-cli.sh`:

```bash
# Запустить тесты внутри контейнера
docker exec -it task-worker-cli-1 /var/www/task/test-worker-cli.sh
```

Или вручную проверить компоненты:

```bash
# Проверить yt-dlp
docker exec -it task-worker-cli-1 yt-dlp --version

# Проверить whisper
docker exec -it task-worker-cli-1 whisper-cli --help
docker exec -it task-worker-cli-1 whisper-server --help

# Проверить diarize.py
docker exec -it task-worker-cli-1 diarize.py --help

# Проверить переменные окружения
docker exec -it task-worker-cli-1 env | grep -E "(WHISPER|PYANNOTE|YT_DLP)"
```

## Пути к файлам в контейнере

- **yt-dlp**: `/opt/yt-dlp/bin/yt-dlp` (ссылка: `/usr/local/bin/yt-dlp`)
- **whisper.cpp**: `/opt/whisper.cpp/`
  - Бинарники: `/opt/whisper.cpp/build/bin/`
  - Модели: `/opt/whisper.cpp/models/`
- **diarize.py**: `/opt/diarize-workdir/diarize.py`
- **config.yaml**: `/opt/diarize-workdir/config.yaml`
- **Python окружение diarize**: `/opt/diarize/`

## Примечания

1. Все компоненты установлены в изолированных Python окружениях для избежания конфликтов
2. Созданы символические ссылки для удобного доступа из PATH
3. Переменные окружения установлены в Dockerfile для соответствия документации
4. Модель `ggml-large-v3.bin` не включена в образ — скачайте её отдельно (например, `cd /opt/whisper.cpp && ./models/download-ggml-model.sh large-v3`) и убедитесь, что файл доступен по пути `/opt/whisper.cpp/models/ggml-large-v3.bin`

## Важные замечания по совместимости

### PyTorch 2.6+ Compatibility
В **diarize.py** добавлено исправление для совместимости с PyTorch 2.6+:

**Проблема:** В PyTorch 2.6+ изменилось значение по умолчанию параметра `weights_only` в `torch.load()` с `False` на `True` для улучшения безопасности. Это вызывает ошибки при загрузке моделей PyAnnote.audio из-за использования определенных классов PyTorch.

**Решение:** В `bin/src/diarize.py` добавлен monkey patch, который:
1. Сохраняет оригинальный метод `torch.load()`
2. Создает обертку, которая устанавливает `weights_only=False`
3. Временно применяет патч во время загрузки pyannote модели
4. Восстанавливает оригинальный `torch.load()` после загрузки

Это безопасно, так как pyannote.audio - это доверенная библиотека из официальных источников.

**Код исправления:**
```python
# Fix for PyTorch 2.6+ weights_only security changes
# pyannote.audio is a trusted library, we can safely use weights_only=False
import pyannote.audio.core.model
original_from_pretrained = pyannote.audio.core.model.Model.from_pretrained

def fixed_from_pretrained(cls, *args, **kwargs):
    # Temporarily patch torch.load to use weights_only=False for pyannote models
    original_torch_load = torch.load
    def patched_torch_load(f, **torch_kwargs):
        torch_kwargs['weights_only'] = False
        return original_torch_load(f, **torch_kwargs)

    # Apply patch and call original method
    torch.load = patched_torch_load
    try:
        return original_from_pretrained(*args, **kwargs)
    finally:
        # Restore original torch.load
        torch.load = original_torch_load

# Monkey patch to fix the weights_only issue
pyannote.audio.core.model.Model.from_pretrained = classmethod(fixed_from_pretrained)
```

Это исправление обеспечивает безопасную загрузку моделей при сохранении функциональности.

## Известные проблемы совместимости

### huggingface_hub + pyannote.audio
**Проблема:** `pyannote.audio` 4.x требует `huggingface_hub>=0.25.0`, но в базовых образах может быть установлена более старая версия.

**Симптомы:** Ошибки типа `TypeError: hf_hub_download() got an unexpected keyword argument 'use_auth_token'`

**Решение:** В Dockerfile закреплена совместимая версия:
```dockerfile
/opt/diarize/bin/pip install --no-cache-dir "huggingface_hub==0.35.3" torch pyannote-audio soundfile speechbrain torchaudio "requests[socks]"
```

### torch.utils._backends.torch_audio_backend
**Предупреждение:** В новых версиях PyTorch 2.2+ появляется предупреждение об устаревшем `torch_audio_backend`. Это не влияет на функциональность.
5. Для работы diarize.py настройте токен HuggingFace: задайте переменную окружения `PYANNOTE_HF_TOKEN`.
