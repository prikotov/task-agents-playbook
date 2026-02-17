# DjVu (ddjvu)

TasK использует `ddjvu` из пакета DjVuLibre, чтобы преобразовать `.djvu` файлы в `.pdf` перед дальнейшей обработкой
Docling/MinerU. Конвертация выполняется на шаге `app:source:transcribe`, оригинальный `.djvu` сохраняется в файловом
хранилище (для повторных запусков пайплайна), а в `additionalParams` записывается его путь. Официальная документация
DjVuLibre: <https://djvu.sourceforge.net/doc/index.html>.

## Требования

- Linux x86_64.
- Пакет `djvulibre` (Fedora/CentOS/AlmaLinux) — предоставляет CLI `ddjvu`.

## Установка (Fedora/CentOS/AlmaLinux)

```bash
sudo dnf install -y djvulibre
```

После установки убедитесь, что бинарник доступен:

```bash
ddjvu --help
```

Если утилита установлена корректно, вы увидите справку по использованию с информацией о версии DjVuLibre.

## Конфигурация

Определите путь к `ddjvu`:

```bash
whereis ddjvu
```

Задайте параметры в `.env.local` (значения по умолчанию указаны ниже) и перезапустите сервисы Symfony:

```dotenv
DJVU_BIN_PATH=/usr/bin/ddjvu   # полный путь из whereis
DJVU_MODE=color                # режим: color | black | foreground | background
DJVU_SCALE=110                 # целевой DPI
DJVU_QUALITY=75                # JPEG-качество (25..150)
DJVU_TIMEOUT=900               # таймаут процесса в секундах
```

Если бинарь установлен в другом каталоге, укажите соответствующий путь в `DJVU_BIN_PATH`.

Эти переменные используются компонентом `DjvuComponent`. При необходимости можно снижать `DJVU_SCALE`/`DJVU_QUALITY`,
чтобы удерживать итоговый PDF в пределах допустимого размера.

## Проверка

```bash
ddjvu -format=tiff -mode=color -scale=110 -quality=75 input.djvu output.tiff
```

Для конвертации напрямую в PDF можно использовать:

```bash
ddjvu -format=ppm input.djvu - | convert - output.pdf
```

Или для тестовой проверки без создания файла:

```bash
ddjvu -format=pbm -mode=black input.djvu /dev/null
```

Полученный PDF должен открываться в браузере без артефактов. После обновления настроек перезапустите пайплайн
`app:source:transcribe` для нужного источника — он пересоздаст PDF и обновит status на `makeDocument`.
