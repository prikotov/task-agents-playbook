# Docling CLI

Инструкция по установке и подключению Docling при первом деплое. Docling преобразует PDF в Markdown/JSON, выделяет структуру и используется в TasK как основной конвертер на шаге `make-document`. Официальная документация: <https://docling-project.github.io/docling/installation/>.

## Требования

- Python 3.10+ (рекомендуем 3.11).
- Утилита `pip` и модуль `venv`.
- Доступ в интернет для загрузки зависимостей (PyPI + репозиторий PyTorch).

## Установка

1. Установите системные зависимости (Fedora/CentOS/AlmaLinux):

   ```bash
   sudo dnf install -y python3 python3-venv python3-pip gcc-c++ make
   ```

2. Создайте виртуальное окружение для Docling (чтобы изолировать Python-зависимости приложения):

   ```bash
   sudo mkdir -p /opt/docling
   sudo python3 -m venv /opt/docling
   #source /opt/docling/bin/activate
   #pip install --upgrade pip
   /opt/docling/bin/pip install --upgrade pip
   ```

3. Установите Docling. Для серверов без GPU используйте колёсо PyTorch для CPU:

   ```bash
   #pip install docling --extra-index-url https://download.pytorch.org/whl/cpu
   /opt/docling/bin/pip install docling #--extra-index-url https://download.pytorch.org/whl/cpu
   ```

   > Для OCR/ASR/VLM функциональности воспользуйтесь extras из официальной документации:
   > `pip install "docling[asr,vlm,easyocr]" --extra-index-url https://download.pytorch.org/whl/cpu`

4. Проверьте установку:

   ```bash
   /opt/docling/bin/docling --version
   ```

5. Определите фактический путь (совпадает с путём бинаря в окружении):

   ```bash
   realpath /opt/docling/bin/docling
   ```

6. Зафиксируйте путь до бинаря в `.env.local`:

   ```dotenv
   DOCLING_BIN_PATH=/opt/docling/bin/docling
   ```

7. Перезапустите PHP/Symfony сервис, чтобы конфигурация подхватила новую переменную (например, `docker compose restart php-fpm`).

## Обновление

- Для обновления Docling активируйте окружение (`source /opt/docling/bin/activate`) и выполните `pip install --upgrade docling`.
- После обновления проверяйте `docling --version`. При проблемах с PyTorch переустановите с тем же параметром `--extra-index-url`.

## Диагностика

- Компонент пишет логи в канал `docling` (Monolog). Проверьте `var/log/prod.log` при ошибках конвертации.
- Наиболее частые причины: не найден бинарь (`DOCLING_BIN_PATH` указан неверно) или отсутствуют системные зависимости для Torch. В этом случае повторите установку и убедитесь, что окружение использует правильный Python.
