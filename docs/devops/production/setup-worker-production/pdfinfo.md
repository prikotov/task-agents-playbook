# pdfinfo (Poppler)

`pdfinfo` из пакета **poppler-utils** используется модулем Source для чтения метаданных PDF во время загрузки файлов:
название документа, тему, дату создания. Без этой утилиты обработка PDF остановится с ошибкой
`Unable to read PDF metadata via pdfinfo`.

## Установка (Fedora/CentOS/AlmaLinux)

```bash
sudo dnf install -y poppler-utils
```

Проверьте версию:

```bash
pdfinfo -v
```

## Конфигурация

Определите путь к бинарю:

```bash
whereis pdfinfo
```

Пропишите значение в `.env.local` и перезапустите сервисы Symfony:

```.dotenv
PDFINFO_BIN_PATH=/usr/bin/pdfinfo
```

Если бинарь установлен в другом каталоге, укажите полный путь в переменной `PDFINFO_BIN_PATH`.

## Тестирование

1. Загрузите любой PDF через web-интерфейс "Create source".
2. В логах (`var/log/prod.log`) не должно быть ошибок `Unable to read PDF metadata via pdfinfo`.
3. В карточке источника появится корректное название (Title) и дата файла.
