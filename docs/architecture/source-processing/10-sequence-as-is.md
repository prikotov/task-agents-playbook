# Sequence Diagram: Обработка источников (AS-IS)

## Диаграмма последовательности

```mermaid
sequenceDiagram
    participant User as Пользователь (Web/CLI)
    participant Controller as Controller
    participant Messenger as Symfony Messenger
    participant DB as База данных
    participant LocalFS as Local FS
    participant S3 as S3/MinIO
    participant Downloader as Downloader
    participant YtDlp as yt-dlp
    participant Diarizer as Diarizer
    participant Transcriber as Transcriber
    participant DocBuilder as Document Builder

    Note over User,DocBuilder: Создание нового источника
    User->>Controller: Создать Source
    Controller->>DB: Сохранить Source (status: new)
    Controller->>Messenger: Отправить команду на запуск workflow
    
    Note over User,DocBuilder: Этап 1: Download (если нужно)
    Messenger->>DB: Найти Source (status: needDownload)
    DB-->>Messenger: Source
    Messenger->>S3: Проверить существование файла
    S3-->>Messenger: Файл не найден
    
    Messenger->>LocalFS: Создать временный файл
    Messenger->>Downloader: download(uri, tempFile)
    
    alt YouTube/RuTube источник
        Downloader->>YtDlp: yt-dlp --extract-audio
        YtDlp->>LocalFS: Сохранить аудио (.ogg)
        YtDlp->>LocalFS: Сохранить метаданные (.json, .description)
        YtDlp-->>Downloader: Готово
    else Прямой источник (audio, text, pdf)
        Downloader->>LocalFS: Скачать файл напрямую
        Downloader-->>Downloader: Готово
    end
    
    LocalFS->>S3: upload: audio.ogg (ключ: ab/cd-ef123.ogg)
    LocalFS->>S3: upload: audio.json (метаданные)
    LocalFS->>S3: upload: audio.description (описание)
    Messenger->>DB: Обновить Source (status: needExtract)
    
    Note over User,DocBuilder: Этап 2: Extract Data
    Messenger->>DB: Найти Source (status: needExtract)
    DB-->>Messenger: Source
    Messenger->>S3: download: audio.ogg → temp/
    S3-->>LocalFS: Файл загружен
    Messenger->>LocalFS: Извлечь метаданные
    LocalFS-->>Messenger: Метаданные извлечены
    Messenger->>DB: Сохранить метаданные
    Messenger->>DB: Обновить Source (status: diarize/transcribe)
    
    Note over User,DocBuilder: Этап 3: Diarize (для аудио/видео)
    Messenger->>DB: Найти Source (status: diarize)
    DB-->>Messenger: Source
    Messenger->>S3: download: audio.ogg → temp/
    S3-->>LocalFS: Файл загружен
    Messenger->>Diarizer: diarize(audio.ogg)
    Diarizer->>LocalFS: Создать файлы диаризации
    LocalFS->>S3: upload: audio.ogg.WhisperCpp.Dyes.0.channel.md
    LocalFS->>S3: upload: audio.ogg.WhisperCpp.Dno.0.channel.md
    Messenger->>DB: Обновить Source (status: transcribe)
    
    Note over User,DocBuilder: Этап 4: Transcribe
    Messenger->>DB: Найти Source (status: transcribe)
    DB-->>Messenger: Source
    Messenger->>S3: download: audio.ogg → temp/
    S3-->>LocalFS: Файл загружен
    Messenger->>S3: download: артефакты диаризации (если есть)
    S3-->>LocalFS: Артефакты загружены
    
    alt Диаризация отключена
        Messenger->>Transcriber: transcribeBasic
    else Диаризация включена
        alt Есть артефакты диаризации
            Messenger->>Transcriber: transcribe на основе артефактов диаризации
        else Нет артефактов диаризации
            Messenger->>Messenger: Ошибка (diarization artifacts missing)
        end
    end
    
    Transcriber->>LocalFS: Создать транскрипт
    LocalFS->>S3: upload: audio.ogg.vtt (WebVTT)
    LocalFS->>S3: upload: audio.ogg.txt (текст)
    LocalFS->>S3: upload: audio.ogg.json (метаданные транскрипции)
    Messenger->>DB: Обновить Source (status: makeDocument)
    
    Note over User,DocBuilder: Этап 5: Make Document
    Messenger->>DB: Найти Source (status: makeDocument)
    DB-->>Messenger: Source
    Messenger->>S3: download: транскрипт → temp/
    S3-->>LocalFS: Файл загружен
    Messenger->>DocBuilder: Построить документ
    DocBuilder->>DB: Сохранить Document
    Messenger->>DB: Обновить Source (status: makeChunks)
    
    Note over User,DocBuilder: Этап 6: Make Document Chunks
    Messenger->>DB: Найти Source (status: makeChunks)
    DB-->>Messenger: Source
    Messenger->>DocBuilder: Создать чанки
    DocBuilder->>DB: Сохранить DocumentChunks
    Messenger->>DB: Обновить Source (status: active)
```

## Легенда участников

| Участник               | Описание                                                     |
| ---------------------- | ------------------------------------------------------------ |
| Пользователь (Web/CLI) | Точка входа - веб-интерфейс или консольная команда           |
| Controller             | Контроллер Symfony, обрабатывающий входящие запросы          |
| Symfony Messenger      | Очередь сообщений для асинхронной обработки                  |
| База данных            | PostgreSQL для хранения метаданных источников                |
| Local FS               | Временная локальная файловая система для обработки           |
| S3/MinIO               | S3-совместимое хранилище для постоянного хранения файлов     |
| Downloader             | Сервис загрузки файлов из различных источников               |
| yt-dlp                 | Утилита для загрузки видео/аудио с YouTube и других платформ |
| Diarizer               | Сервис диаризации (разделения речи по спикерам)              |
| Transcriber            | Сервис транскрибации (преобразования речи в текст)           |
| Document Builder       | Сервис создания документов и чанков                          |

## Потоки данных (Local ↔ S3)

### Этап Download
- **Local → S3**: 
  - `audio.ogg` (основной файл) - ключ: `ab/cd-ef123.ogg`
  - `audio.json` (метаданные) - ключ: `ab/cd-ef123.json`
  - `audio.description` (описание) - ключ: `ab/cd-ef123.description`

### Этап Extract
- **S3 → Local**: `audio.ogg` для извлечения метаданных
- **Local → S3**: дополнительные артефакты (если создаются)

### Этап Diarize
- **S3 → Local**: `audio.ogg` для диаризации
- **Local → S3**: 
  - `audio.ogg.WhisperCpp.Dyes.0.channel.md` (диаризация)
  - `audio.ogg.WhisperCpp.Dno.0.channel.md` (метаданные диаризации)

### Этап Transcribe
- **S3 → Local**: 
  - `audio.ogg` для транскрибации
  - Артефакты диаризации (если есть)
- **Local → S3**:
  - `audio.ogg.vtt` (WebVTT транскрипт)
  - `audio.ogg.txt` (текст транскрипта)
  - `audio.ogg.json` (метаданные транскрипции)

### Этап Make Document/Chunks
- **S3 → Local**: Транскрипт для создания документов
- **Local → S3**: Дополнительные артефакты документов (если создаются)

### Повторные операции
- **S3 → Local (повтор)**: При каждом этапе обработки файлы повторно скачиваются из S3 во временную директорию
- **Local → S3 (повтор)**: После каждого этапа обработки артефакты загружаются обратно в S3

## Ссылки на код

### Основные компоненты workflow
- [`DetermineSourceWorkflowStepService`](src/Module/Source/Application/Service/SourceWorkflow/SourceWorkflowResolver/DetermineSourceWorkflowStepService.php) - Определение следующего шага обработки
- [`LaunchNextSourceWorkflowStepService`](src/Module/Source/Application/Service/SourceWorkflow/LaunchNextSourceWorkflowStep/LaunchNextSourceWorkflowStepService.php) - Запуск следующего шага

### Обработчики команд
- [`DownloadCommandHandler`](src/Module/Source/Application/UseCase/Command/Source/Download/DownloadCommandHandler.php) - Загрузка файлов
- [`ExtractDataCommandHandler`](src/Module/Source/Application/UseCase/Command/Source/ExtractData/ExtractDataCommandHandler.php) - Извлечение данных
- [`DiarizeCommandHandler`](src/Module/Source/Application/UseCase/Command/Source/Diarize/DiarizeCommandHandler.php) - Диаризация
- [`TranscribeCommandHandler`](src/Module/Source/Application/UseCase/Command/Source/Transcribe/TranscribeCommandHandler.php) - Транскрибация
- [`MakeDocumentCommandHandler`](src/Module/Source/Application/UseCase/Command/Source/MakeDocument/MakeDocumentCommandHandler.php) - Создание документов
- [`MakeDocumentChunksCommandHandler`](src/Module/Source/Application/UseCase/Command/Source/MakeDocumentChunks/MakeDocumentChunksCommandHandler.php) - Создание чанков

### Файловое хранилище
- [`S3FileStorage`](src/Component/FileStorage/S3/S3FileStorage.php) - Реализация S3 хранилища
- [`FileStorageService`](src/Module/Source/Domain/Service/FileStorage/FileStorageService.php) - Сервис файлового хранилища
- [`FileWorkspaceService`](src/Module/Source/Infrastructure/Service/FileStorage/FileWorkspaceService.php) - Сервис работы с временными файлами

### Синхронизация артефактов
- [`SyncWorkspaceArtifactsService`](src/Module/Source/Infrastructure/Service/Artifact/SyncWorkspaceArtifactsService.php) - Синхронизация артефактов между S3 и локальной FS
- [`DiarizationArtifactsService`](src/Module/Source/Application/Service/Diarization/DiarizationArtifactsService.php) - Управление артефактами диаризации

### Внешние компоненты
- [`YtDlpComponent`](src/Module/Source/Infrastructure/Component/YtDlp/YtDlpComponent.php) - Интеграция с yt-dlp
- [`YouTubeDownloaderService`](src/Module/Source/Infrastructure/Service/Source/Downloader/YouTubeDownloaderService.php) - Загрузчик YouTube
- [`DiarizerService`](src/Module/Source/Integration/Service/Diarizer/DiarizerService.php) - Сервис диаризации

## TODO/Неясно

1. **Формат ключей S3**: Точный алгоритм формирования ключей в S3 требует дополнительного анализа в [`S3FileStorage::generateUniqueId()`](src/Component/FileStorage/S3/S3FileStorage.php:411)
2. **Обработка ошибок**: Механизм отката при ошибках загрузки в S3 не полностью ясен из текущего кода
3. **Очистка временных файлов**: Не до конца понятно, когда и как происходит очистка временных файлов после каждого этапа
4. **Конкурентная обработка**: Неясно, как обрабатываются ситуации, когда несколько этапов пытаются работать с одним файлом одновременно
5. **Механизм кэширования**: Неясно, есть ли кэширование при повторном скачивании одних и тех же файлов из S3
6. **Мониторинг прогресса**: Механизм отслеживания прогресса длительных операций (загрузка, транскрибация) требует дополнительного анализа
