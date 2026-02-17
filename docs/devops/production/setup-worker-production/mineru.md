# MinerU

MinerU преобразует PDF в Markdown, извлекает таблицы и формулы. В TasK он используется как альтернатива Docling для шага `make-document`.
Официальная документация: <https://opendatalab.github.io/MinerU/quick_start/>.

## Требования

- Linux x86_64
- Python 3.10+ (MinerU не поддерживает Python 3.14+)
- ~8 ГБ RAM и 15+ ГБ свободного места на диске

## Установка

1. Установите Python и инструменты сборки (Fedora/CentOS/AlmaLinux):
   ```bash
   sudo dnf install -y python3 python3-pip python3-virtualenv gcc gcc-c++ make
   ```

2. Создайте виртуальное окружение и обновите `pip`:
   ```bash
   sudo mkdir -p /opt/mineru
   python3 -m venv /opt/mineru
   #source /opt/mineru/bin/activate
   #pip install --upgrade pip
   /opt/mineru/bin/pip install --upgrade pip
   ```

3. Установите MinerU с CPU-зависимостями (при необходимости укажите корпоративный proxy):
   ```bash
   HTTPS_PROXY=http://user:pass@proxy:port \
   HTTP_PROXY=http://user:pass@proxy:port \
   #pip install "mineru[core]" --extra-index-url https://download.pytorch.org/whl/cpu
   /opt/mineru/bin/pip install "mineru[core]"
   ```

4. Проверьте установку:
   ```bash
   /opt/mineru/bin/mineru --version
   ```

5. Определите путь к бинарю (если окружение располагается в другом каталоге):
   ```bash
   realpath /opt/mineru/bin/mineru
   ```

## Конфигурация

В `.env.local` добавьте (или обновите) переменные:

```dotenv
MINERU_BIN_PATH=/opt/mineru/bin/mineru   # путь из realpath
MINERU_METHOD=auto
MINERU_BACKEND=pipeline
MINERU_LANGUAGE=east_slavic
MINERU_DEVICE=
MINERU_START_PAGE=-1      # -1 = весь документ
MINERU_END_PAGE=-1        # -1 = весь документ
MINERU_FORMULA_ENABLED=1
MINERU_TABLE_ENABLED=1
MINERU_TIMEOUT=3600       # секунды
MINERU_NUM_THREADS=8
MINERU_DPI=110            # DPI при растеризации страниц
MINERU_PAGES_PER_BATCH=2000  # сколько страниц конвертируем за раз
MINERU_PROXY=             # опционально: http://user:pass@proxy:port

# docling | mineru
SOURCE_PDF_BUILDER=docling
```

- Значения `MINERU_LANGUAGE`, `MINERU_DEVICE` можно оставить пустыми (для русскоязычных PDF рекомендуется `east_slavic`, для англоязычных — `latin`).
- При необходимости отключите распознавание формул/таблиц (`0`).
- `MINERU_DPI` уменьшает разрешение страниц при растеризации (по умолчанию 110 DPI), что снижает потребление памяти.
- `MINERU_PAGES_PER_BATCH` задаёт количество страниц в одном запуске MinerU; при превышении лимита документ режется на диапазоны и результаты склеиваются.
- `SOURCE_PDF_BUILDER=mineru` переключает пайплайн PDF на MinerU (по умолчанию используется Docling).
- Если окружение без прямого доступа в интернет, задайте `MINERU_PROXY` — компонент пробросит его в `HTTP(S)_PROXY` для загрузки моделей HuggingFace.
- Все артефакты MinerU сохраняются рядом с исходным PDF в каталоге `<pdf-basename>/chunk_xxxx`, поэтому они доступны через `/artifact-files/...` даже после падений.

После изменения `.env.local` перегрузите сервисы или выполните команды заново.

## Проверка

```bash
sudo -u wwwtask /opt/task/current/bin/console \
    app:source:make-document --source-uuid=<uuid>
```

В логах появится канал `mineru`, а в каталоге `var/log/web/prod.log` (или dev) отразятся команды MinerU.
