# Пайплайн обработки источников

## Общая схема

1. Пользователь создаёт источник через Web UI (`/source/create?projectUuid=...`). Допустимы три режима ввода: URL, файл, текст. После сохранения объект получает статус `new`.
2. Далее источник проходит обязательную последовательность консольных команд (см. `apps/console/src/Module/Source/Command/README.md`):

| Шаг | Команда | Очередь Messenger | Назначение | Статус/флаги |
| --- | --- | --- | --- | --- |
| 1 | `app:source:download` | `source_download` | Скачивает оригинальный контент (видео/HTML/GitHub ZIP...) в файловое хранилище. | Статус: `needDownload → needExtract`. |
| 2 | `app:source:extract-data` (алиас `app:source:extract`) | `source_extract` | Извлекает метаданные, обновляет counters, сохраняет комментарии (YouTube/RuTube). | Статус: `needExtract → transcribe`. |
| 3 | `app:source:transcribe` | `source_transcribe` | Для аудио/видео типов гоняет транскрибёр и создаёт текстовые артефакты. | Статус → `makeDocument`. |
| 4 | `app:source:make-document` | `source_make_document` | Формирует документы RAG. GitHub источники превращаются в несколько документов (по одному на файл). | Статус → `makeChunks`. |
| 5 | `app:source:make-document-chunks` | `source_make_document_chunks` | Режет документы на чанки, подготавливает RAG-хранилище. | Статус → `active`. |

> **Важно:** команды следует запускать строго по порядку. Пропуск `download` приведёт к ошибкам на последующих стадиях.

Ошибки на любой стадии переводят источник в `error`. Сбросить их можно через `app:source:reset-download-error` (или аналогичную команду для конкретного шага).

## Воркеры и защита от повторного запуска

- Для каждой очереди используйте отдельные воркеры через `php bin/console messenger:consume <queue>` (systemd/supervisor/Docker как описано в [`../devops/production/setup-worker-production/messenger.md`](../devops/production/setup-worker-production/messenger.md)). Типичная команда: `php bin/console messenger:consume source_extract --env=prod --no-interaction --sleep=1 --memory-limit=512M --verbose`.
- CLI-команды оставлены в проекте для ручного запуска и используют `LockFactory` (`LOCK_RESOURCE` в каждой команде), поэтому параллельные вызовы одной команды не стартуют одновременно.
- Чтобы не дублировать обработку одного и того же Source между воркерами и CLI, ориентируйтесь на статусы: `downloadInProgress`/`extractInProgress`/`transcribeInProgress`/`makeDocumentInProgress`/`makeChunksInProgress` фиксируют занятость шага, `needDownload`/`needExtract`/`transcribe`/`makeDocument`/`makeChunks` обозначают готовность к конкретному шагу.
- В CI или dev-окружениях с отключённым RabbitMQ можно временно выставлять `SOURCE_*_TRANSPORT_DSN=sync://`, а команды выполнять последовательным Cron-скриптом.

## Операционный runbook: пересборка чанков (`mark + enqueue -> worker`)

Для восстановления документов после инцидентов с контекстным окном используйте одну команду, которая в одном запуске:
1. выбирает документы в статусе `ready`;
2. маркирует их для пересборки;
3. отправляет в очередь `source_make_document_chunks`.

Перед запуском зафиксируйте cutoff по `updStatusTs` (один и тот же для всей кампании), чтобы в повторных cron-запусках
не подхватывать документы, которым уже сделали rechunk.

```bash
# фиксируем cutoff в начале кампании
CUTOFF="2026-02-11T10:00:00Z"

# Dry-run (проверка выборки без отправки сообщений)
bin/console app:source:send-documents-for-rechunk --status-updated-before="$CUTOFF" --limit=100 --interval-ms=200 --dry-run

# Массовый enqueue пакетами
bin/console app:source:send-documents-for-rechunk --status-updated-before="$CUTOFF" --limit=100 --interval-ms=200

# Точечный enqueue для одного source / проекта / документа
bin/console app:source:send-documents-for-rechunk --status-updated-before="$CUTOFF" --source-uuid=<source-uuid> --limit=100 --interval-ms=200
bin/console app:source:send-documents-for-rechunk --status-updated-before="$CUTOFF" --project-uuid=<project-uuid> --limit=100 --interval-ms=200
bin/console app:source:send-documents-for-rechunk --status-updated-before="$CUTOFF" --document-uuid=<document-uuid> --limit=1 --interval-ms=0
```

После успешной отправки документ переводится в `needChunks`, чтобы следующий cron-запуск не ставил тот же документ повторно.

### Cron-сценарий

Запускайте enqueue периодически, чтобы контролировать нагрузку на воркеры:

```bash
*/5 * * * * php /var/www/task/bin/console app:source:send-documents-for-rechunk --status-updated-before="2026-02-11T10:00:00Z" --limit=100 --interval-ms=200 --no-interaction
```

Параметры подбираются под пропускную способность очереди:
- `--limit` — размер пачки за один запуск.
- `--interval-ms` — пауза между отправками сообщений.

## GitHub источники

1. В форме `/source/create` укажите ссылку вида:
   - `https://github.com/<owner>/<repo>` — весь репозиторий (будет использована дефолтная ветка);
   - `https://github.com/<owner>/<repo>/tree/<ref>/<path>` — конкретная директория;
   - `https://github.com/<owner>/<repo>/blob/<ref>/<path>` — одиночный файл.
   Где `<ref>` — branch, tag или commit hash.
2. `app:source:download` использует `git clone --depth=1` (env `GITHUB_GIT_BINARY`, `GITHUB_GIT_TIMEOUT`) и выгружает запрошенный каталог/файл напрямую в файловое хранилище как отдельную директорию.
3. `app:source:make-document` распаковывает архив и обходит файлы:
   - бинарные и пустые файлы пропускаются;
   - каждый текстовый файл становится отдельным Document;
   - в meta фиксируются `Repository`, `Reference`, `Original path`, чтобы было понятно, откуда взят текст.

После `app:source:make-document-chunks` такой источник ничем не отличается от остальных и готов к использованию в чатах и RAG-запросах.

### Semantics of `ref` for refresh

- Если `ref` — branch: при `mode=full` refresh всегда делает повторный export на текущий HEAD ветки и затем перегенерирует documents/chunks.
- Если `ref` — tag или commit hash: `mode=full` refresh делает повторный export на тот же `ref` (snapshot) и затем перегенерирует documents/chunks.

## PDF и DjVu источники

1. При создании источника можно указать URL или загрузить файл c расширением `.pdf` или `.djvu`. Тип определяется по имени файла либо через `Content-Type: application/pdf` / `image/vnd.djvu`.
2. На шаге `app:source:download` работает `PdfDownloaderService`: PDF-файл скачивается целиком и сохраняется в файловом хранилище как артефакт (`.pdf`), поэтому важно удостовериться, что источник доступен по прямой ссылке.
3. `app:source:extract-data` использует `TextDataExtractorService` для PDF и ограничивается базовыми счётчиками, поэтому дополнительных метаданных не будет — это нормальное поведение.
4. `app:source:transcribe` все ещё требуется запускать, чтобы перевести источник в статус `makeDocument`. Для PDF этот шаг лишь меняет статус. Для DjVu на этом этапе запускается конвертация `ddjvu` → PDF (используются переменные `DJVU_*`), оригинальный файл остаётся в хранилище и его путь сохраняется в `additionalParams`.
5. На шаге `app:source:make-document` подключается конвертер PDF → Markdown:
   - **По умолчанию** используется Docling (`DoclingPdfDocumentContentsBuilder`, см. [`../devops/production/setup-worker-production/docling.md`](../devops/production/setup-worker-production/docling.md)).
   - **Альтернатива** — MinerU (`MineruPdfDocumentContentsBuilder`, см. [`../devops/production/setup-worker-production/mineru.md`](../devops/production/setup-worker-production/mineru.md)). Чтобы переключить пайплайн, задайте `SOURCE_PDF_BUILDER=mineru` и настройте путь `MINERU_BIN_PATH=/opt/mineru/bin/mineru`. Дополнительные параметры (`MINERU_METHOD`, `MINERU_BACKEND`, `MINERU_LANGUAGE`, `MINERU_TIMEOUT`, `MINERU_NUM_THREADS` и др.) прокидываются в CLI.
   - MinerU складывает все артефакты рядом с исходным PDF в каталоге `<pdf-basename>/chunk_xxxx`, поэтому после падений можно просто посмотреть `/artifact-files/...` и перезапустить конвертацию без потери промежуточных файлов.
   - Чтобы снизить потребление памяти, MinerU запускается пакетами: при превышении `MINERU_PAGES_PER_BATCH` (по умолчанию 2000) документ режется на диапазоны страниц и результаты склеиваются в один Markdown; DPI можно дополнительно ограничить переменной `MINERU_DPI`.
   Оба конвертера приводят результат к Markdown, нормализуют пустые строки и добавляют базовую метаинформацию.
6. `app:source:make-document-chunks` обрабатывает PDF-документ так же, как и любые другие markdown-источники.
