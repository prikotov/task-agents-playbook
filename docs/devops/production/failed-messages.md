# Работа с failed messages в Symfony Messenger

**Failed messages** — сообщения, которые не удалось обработать после всех попыток повторной отправки. Они попадают в.failure transport и хранятся в таблице `failed_queue_messages` базы данных.

## Границы ответственности

Этот документ описывает работу с failed messages через Symfony Messenger CLI:
- Просмотр списка и деталей failed messages
- Повторная отправка сообщений
- Удаление сообщений

За настройку failure transport и retry_strategy отвечает [`config/packages/messenger.yaml`](../../config/packages/messenger.yaml). Подробности о настройке воркеров — в [`setup-worker-production/messenger.md`](setup-worker-production/messenger.md).

## Команды для работы с failed messages

### messenger:failed:show — просмотр failed messages

Показывает сообщения, которые находятся в failure transport.

```bash
# Показать все failed messages (по умолчанию до 50)
bin/console messenger:failed:show

# Показать конкретное сообщение по ID
bin/console messenger:failed:show <id>

# Показать статистику по классам сообщений
bin/console messenger:failed:show --stats

# Отфильтровать по конкретному классу
bin/console messenger:failed:show --class-filter="Common\Module\Source\Application\UseCase\Command\Source\MakeDocumentChunks\MakeDocumentChunksCommand"

# Ограничить количество сообщений
bin/console messenger:failed:show --max=10
```

### messenger:failed:retry — повторная отправка сообщений

Повторно отправляет сообщение(я) из failure transport на обработку.

```bash
# Интерактивный режим: спрашивает для каждого сообщения
bin/console messenger:failed:retry

# Повторить конкретное сообщение по ID
bin/console messenger:failed:retry <id>

# Повторить несколько сообщений сразу
bin/console messenger:failed:retry <id1> <id2> <id3>

# Принудительное повторение без подтверждения
bin/console messenger:failed:retry --force <id>
```

### messenger:failed:remove — удаление сообщений

Удаляет сообщения из failure transport.

```bash
# Удалить конкретное сообщение по ID
bin/console messenger:failed:remove <id>

# Удалить несколько сообщений
bin/console messenger:failed:remove <id1> <id2>

# Удалить все failed messages
bin/console messenger:failed:remove --all

# Принудительное удаление без подтверждения
bin/console messenger:failed:remove --force <id>

# Показать сообщения перед удалением
bin/console messenger:failed:remove --show-messages <id1> <id2>

# Отфильтровать по классу и удалить
bin/console messenger:failed:remove --class-filter="Common\Module\Source\Application\UseCase\Command\Source\MakeDocumentChunks\MakeDocumentChunksCommand" --all
```

## Примеры вывода команд

### Пример вывода messenger:failed:show

```bash
$ bin/console messenger:failed:show
 ------------------- --------------------- ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  Id                 Class                Message                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     Error
 ------------------- --------------------- ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  1                  Common\Module\...    Common\Module\Source\Application\UseCase\Command\Source\MakeDocumentChunks\MakeDocumentChunksCommand^@AdocumentUuid:"9a3b4c5d-6e7f-8a9b-0c1d-2e3f4a5b6c7d";^@AworkflowContext:Common\Module\Source\Application\ValueObject\SourceWorkflowExecutionContextVo^@A<^@A<^@AsourceUuid:"1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d";^@A<^@Astages:Common\Module\Source\Application\ValueObject\SourceWorkflowStageVo^@A<^@A<^@Astage:"download";^@Astatus:"completed";^@A<^@A<^@Astage:"extract";^@Astatus:"completed";^@A<^@A<^@Astage:"convert";^@Astatus:"completed";^@A<^@A<^@Astage:"transcribe";^@Astatus:"pending";^@A<^@A<^@Astage:"makeDocument";^@Astatus:"completed";^@A<^@A<^@Astage:"makeDocumentChunks";^@Astatus:"pending";^@A<^@A<^@A;<^@A<^@A<^@A;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         Failed to connect to Qdrant: Connection refused
  2                  Common\Module\...    Common\Module\Source\Application\UseCase\Command\Source\Download\DownloadCommand^@A<^@A<^@AsourceUuid:"2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e";^@A<^@A<^@A;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               File not found: /tmp/source_123.pdf
  3                  Common\Module\...    Common\Module\Source\Application\UseCase\Command\Source\MakeDocument\MakeDocumentCommand^@A<^@A<^@AsourceUuid:"3c4d5e6f-7a8b-9c0d-1e2f-3a4b5c6d7e8f";^@AprojectUuid:"4d5e6f7a-8b9c-0d1e-2f3a-4b5c6d7e8f9a";^@AenableLLMCorrection:false;^@A<^@A<^@A;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           Ollama service unavailable: 503 Service Unavailable
 ------------------- --------------------- ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
```

### Пример вывода messenger:failed:show --stats

```bash
$ bin/console messenger:failed:show --stats
  Count  Class
 ------  ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  42     Common\Module\Source\Application\UseCase\Command\Source\MakeDocumentChunks\MakeDocumentChunksCommand
  15     Common\Module\Source\Application\UseCase\Command\Source\Download\DownloadCommand
  8      Common\Module\Source\Application\UseCase\Command\Source\MakeDocument\MakeDocumentCommand
  3      Common\Module\Source\Application\UseCase\Command\Source\ExtractData\ExtractDataCommand
 ------  ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
```

### Пример вывода для конкретного сообщения

```bash
$ bin/console messenger:failed:show 1
 ------------------- --------------------- ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  Id                 Class                Message                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     Error
 ------------------- --------------------- ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  1                  Common\Module\...    Common\Module\Source\Application\UseCase\Command\Source\MakeDocumentChunks\MakeDocumentChunksCommand^@AdocumentUuid:"9a3b4c5d-6e7f-8a9b-0c1d-2e3f4a5b6c7d";^@AworkflowContext:Common\Module\Source\Application\ValueObject\SourceWorkflowExecutionContextVo^@A<^@A<^@AsourceUuid:"1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d";^@A<^@Astages:Common\Module\Source\Application\ValueObject\SourceWorkflowStageVo^@A<^@A<^@Astage:"download";^@Astatus:"completed";^@A<^@A<^@Astage:"extract";^@Astatus:"completed";^@A<^@A<^@Astage:"convert";^@Astatus:"completed";^@A<^@A<^@Astage:"transcribe";^@Astatus:"pending";^@A<^@A<^@Astage:"makeDocument";^@Astatus:"completed";^@A<^@A<^@Astage:"makeDocumentChunks";^@Astatus:"pending";^@A<^@A<^@A;<^@A<^@A<^@A;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         Failed to connect to Qdrant: Connection refused
 ------------------- --------------------- ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
```

## Где искать sourceUuid и другие ключевые данные

В выводе `messenger:failed:show` сообщение сериализовано в формате PHP, который Symfony Messenger использует для хранения. Ключевые данные находятся в поле `Message`:

### Разбор формата сообщения

```
Common\Module\Source\Application\UseCase\Command\Source\MakeDocumentChunks\MakeDocumentChunksCommand^@AdocumentUuid:"UUID";^@AworkflowContext:...
```

Разделим по разделителю `^@A`:
1. Класс команды: `Common\Module\Source\Application\UseCase\Command\Source\MakeDocumentChunks\MakeDocumentChunksCommand`
2. Параметры команды:
   - `documentUuid:"9a3b4c5d-6e7f-8a9b-0c1d-2e3f4a5b6c7d"` — UUID документа
   - `workflowContext:...` — контекст выполнения (если есть)

### Поиск sourceUuid для разных команд

| Команда | Где искать sourceUuid |
|---------|----------------------|
| [`MakeDocumentChunksCommand`](../../src/Module/Source/Application/UseCase/Command/Source/MakeDocumentChunks/MakeDocumentChunksCommand.php:17) | Параметр `documentUuid` (UUID документа) |
| [`DownloadCommand`](../../src/Module/Source/Application/UseCase/Command/Source/Download/DownloadCommand.php:17) | Параметр `sourceUuid` (UUID источника) |
| [`MakeDocumentCommand`](../../src/Module/Source/Application/UseCase/Command/Source/MakeDocument/MakeDocumentCommand.php:18-19) | Параметры `sourceUuid` (UUID источника) и `projectUuid` (UUID проекта) |
| [`ExtractDataCommand`](../../src/Module/Source/Application/UseCase/Command/Source/ExtractData/ExtractDataCommand.php) | Параметр `sourceUuid` (UUID источника) |
| [`ConvertDjvuCommand`](../../src/Module/Source/Application/UseCase/Command/Source/Convert/ConvertDjvuCommand.php) | Параметр `sourceUuid` (UUID источника) |

### Пример: извлечение sourceUuid из MakeDocumentChunksCommand

Вывод команды:
```
MakeDocumentChunksCommand^@AdocumentUuid:"9a3b4c5d-6e7f-8a9b-0c1d-2e3f4a5b6c7d";^@AworkflowContext:Common\Module\Source\Application\ValueObject\SourceWorkflowExecutionContextVo^@A<^@A<^@AsourceUuid:"1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d";...
```

**sourceUuid**: `1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d`

Обратите внимание:
1. Сначала идёт `documentUuid` — UUID документа, для которого создавались чанки
2. Внутри `workflowContext` находится `sourceUuid` — UUID исходного источника

Для поиска в БД используйте:
```sql
SELECT * FROM source WHERE uuid = '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d';
```

### Пример: извлечение sourceUuid из DownloadCommand

Вывод команды:
```
DownloadCommand^@A<^@A<^@AsourceUuid:"2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e";^@A<^@A<^@A;
```

**sourceUuid**: `2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e`

## Практический сценарий работы с failed messages

### Сценарий 1: Ошибка подключения к Qdrant (MakeDocumentChunksCommand)

1. **Показать все failed messages:**
   ```bash
   bin/console messenger:failed:show
   ```

2. **Отфильтровать по классу:**
   ```bash
   bin/console messenger:failed:show --class-filter="Common\Module\Source\Application\UseCase\Command\Source\MakeDocumentChunks\MakeDocumentChunksCommand"
   ```

3. **Показать детали конкретного сообщения и извлечь sourceUuid:**
   ```bash
   bin/console messenger:failed:show 1
   ```

4. **Исправить проблему** (например, перезапустить Qdrant)

5. **Повторить сообщение:**
   ```bash
   bin/console messenger:failed:retry 1
   ```

### Сценарий 2: Файл не найден (DownloadCommand)

1. **Показать статистику:**
   ```bash
   bin/console messenger:failed:show --stats
   ```

2. **Показать детали и извлечь sourceUuid:**
   ```bash
   bin/console messenger:failed:show 2
   ```

3. **Проверить Source в БД:**
   ```sql
   SELECT * FROM source WHERE uuid = '2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e';
   ```

4. **Если Source уже удалён — удалить failed message:**
   ```bash
   bin/console messenger:failed:remove 2
   ```

5. **Если Source валидный и файл доступен — повторить:**
   ```bash
   bin/console messenger:failed:retry 2
   ```

### Сценарий 3: Массовая очистка по классу

Удалить все failed messages для определённого класса (например, после рефакторинга):

```bash
# Сначала посмотреть сколько сообщений будет удалено
bin/console messenger:failed:show --class-filter="Common\Module\Source\Application\UseCase\Command\Source\ExtractData\ExtractDataCommand"

# Удалить все (с подтверждением)
bin/console messenger:failed:remove --class-filter="Common\Module\Source\Application\UseCase\Command\Source\ExtractData\ExtractDataCommand" --all
```

### Сценарий 4: Массовый retry

Повторить все failed messages определённого типа:

```bash
# Посмотреть список ID
bin/console messenger:failed:show --class-filter="Common\Module\Source\Application\UseCase\Command\Source\MakeDocument\MakeDocumentCommand"

# Интерактивный retry для всех
bin/console messenger:failed:retry <id1> <id2> <id3>

# Или использовать pipe (в bash)
bin/console messenger:failed:show | grep "MakeDocumentCommand" | awk '{print $2}' | xargs bin/console messenger:failed:retry
```

## Мониторинг и алерты

Рекомендуется настроить мониторинг для таблицы `failed_queue_messages`:

```sql
-- Проверка количества failed messages
SELECT COUNT(*) FROM failed_queue_messages;

-- Проверка по классам
SELECT class_name, COUNT(*) as count
FROM failed_queue_messages
GROUP BY class_name
ORDER BY count DESC;
```

Добавьте алерты на:
- Общее количество failed messages (> X за период)
- Наличие failed messages в определённых очередях (например, `source_make_document_chunks`)

## Чеклист

- [ ] Перед retry убедитесь, что проблема исправлена
- [ ] Для Source pipeline проверьте артефакты перед retry (см. [`setup-worker-production/messenger.md`](setup-worker-production/messenger.md#failure-transport))
- [ ] После retry убедитесь, что сообщение успешно обработано
- [ ] Удаляйте failed messages только если они не нужны (используйте `--show-messages`)
- [ ] Настройте мониторинг для `failed_queue_messages`
- [ ] Периодически проверяйте failed messages (например, раз в день)
- [ ] Для массовых операций используйте `--force` только после проверки
