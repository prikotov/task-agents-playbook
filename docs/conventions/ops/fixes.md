# Fixes

Фиксы — это временные консольные скрипты для исправления данных в базе, восстановления инвариантов,
заполнения новых данных или разовых корректировок после бага. Фикс не является миграцией и не должен
применяться автоматически на всех окружениях.

## Когда использовать

- Разовая правка данных в конкретном окружении.
- Нужен dry-run перед применением изменений.
- Нужно сохранить backup изменяемых данных.

Если изменение должно применяться во всех окружениях, используйте миграции или feature-код.

## Структура фикса

```
apps/console/src/Module/Fix/Command/FYYMMDDShortName/RunCommand.php
```

Пример имени: `F251220SyncSourceSequence`.

Внутри папки фикса можно хранить вспомогательные классы, используемые только этим фиксом.

## Требования

- Фикс по умолчанию работает в dry-run режиме. Для применения изменений нужен `--execute`.
- Для изменений данных обязателен CSV backup до модификаций.
- Изменения делать батчами (обычно 1 000–10 000 записей за транзакцию).
- По завершению выводить статистику и результаты.

## CSV backup

CSV сохраняется в:

```
var/fix/{FixName}/{filePrefix}-{Ymd-His}.csv
```

Пример записи:

```php
$path = $backupWriter->write(
    fixName: 'F251220SyncSourceSequence',
    filenamePrefix: 'source-before',
    headers: ['id', 'uri'],
    rows: $rows,
);
```

## Шаблон фикса

```php
#[AsCommand(name: 'app:fix:FYYMMDDShortName', description: '...')]
final class RunCommand extends AbstractFixCommand
{
    public function __construct(/* dependencies */)
    {
        parent::__construct();
    }

    protected function configureFix(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->createIo($input, $output);

        if (!$this->requireExecute($input, $io)) {
            return Command::SUCCESS;
        }

        // ... business logic

        $io->success('Done');

        return Command::SUCCESS;
    }
}
```

## Запуск

Dry-run (локально):

```
bin/console app:fix:FYYMMDDShortName
```

Применение (локально):

```
bin/console app:fix:FYYMMDDShortName --execute
```

Запуск в dev окружении (podman, контейнер уже запущен):

```
podman ps
podman exec task-worker-cli-1 bin/console app:fix:FYYMMDDShortName
podman exec task-worker-cli-1 bin/console app:fix:FYYMMDDShortName --execute
```

Запуск в dev окружении (docker compose, сервис уже запущен в рамках compose):

```
docker compose ls
docker compose -p task ps
docker compose -p task exec worker-cli bin/console app:fix:FYYMMDDShortName
docker compose -p task exec worker-cli bin/console app:fix:FYYMMDDShortName --execute
```


## Удаление старых фиксов

Фиксы удаляются отдельным PR, когда они больше не нужны (обычно 6–12 месяцев).
Перед удалением убедитесь, что fix не используется и задокументирован результат применения.
