# Консольная команда презентационного слоя (Presentation Console Command)

## Определение

**Консольная команда (Presentation Console Command)** — класс, связывающий вызов CLI с приложением: парсит
аргументы/опции, валидирует входные данные, вызывает UseCase-ы Application-слоя и формирует человекочитаемый вывод/код
завершения. Основано на [Symfony Console](https://symfony.com/doc/current/components/console.html).

## Общие правила

- Команду объявляем `final`, помечаем `#[AsCommand]` и храним в `apps/<app>/src/Module/<Module>/Command`.
- Публичные методы не добавляем (кроме наследуемых от `Command`); основная логика — в `configure()` и `execute()`.
- Внедряем **только** публичные интерфейсы Application-слоя (`CommandBusComponentInterface`,
  `QueryBusComponentInterface`) и сервисы Presentation (например, `LockFactory`, форматтеры вывода).
- Валидацию формы входа (аргументы/опции) выполняем **до** вызова Application (приведение типов, UUID, диапазоны,
  required).
- Команда должна быть «cron-friendly»: детерминированный вывод, корректные коды завершения, отсутствие лишнего шума.
- Параллельные запуски блокируем через `LockFactory` (или аналог), чтобы избежать гонок.

## Зависимости

- **Разрешено:** `CommandBusComponentInterface`, `QueryBusComponentInterface`, `LockFactory`, форматтеры/презентеры, value object слоя Presentation.
- **Запрещено:** любые зависимости из `Domain/*`, `Infrastructure/*`, `Integration/*`, прямой доступ к ORM/репозиториям, сетевые вызовы в обход Application.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Command/<SubjectName>/<ActionName>Command.php
```

## Как используем

1. **Парсим вход:** описываем аргументы/опции в `configure()`, в `execute()` читаем и валидируем (приводим типы, проверяем UUID).
2. **Проверяем конкурентность:** берём `Lock` (если требуется), корректно освобождаем в `finally`.
3. **Вызываем Application:** через `QueryBus`/`CommandBus`.
4. **Формируем вывод:** через `SymfonyStyle` (`$io`) — чёткий текст/таблица/прогресс; ошибки показываем через `$io->error()`.
5. **Код завершения:** `Command::SUCCESS` при успехе, `Command::FAILURE` при фатальной ошибке; предупреждения не должны ронять команду.
6. **Продвинутые режимы (по необходимости):** `--dry-run`, `--limit`, `--verbose`, страничная обработка/батчи, прогресс-бар.

## Пример

```php
<?php

declare(strict_types=1);

namespace Console\Module\Source\Command;

use Common\Application\Component\CommandBus\CommandBusComponentInterface;
use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Application\Dto\PaginationDto;
use Common\Exception\NotFoundExceptionInterface;
use Common\Module\Source\Application\UseCase\Command\Source\Download\DownloadCommand as ApplicationDownloadCommand;
use Common\Module\Source\Application\UseCase\Query\Source\GetForDownload\GetForDownloadQuery;
use Exception;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:source:download',
    description: 'Скачать данные из источников',
)]
final class DownloadCommand extends Command
{
    private const string OPT_PROJECT_UUID = 'project-uuid';
    private const string OPT_SOURCE_UUID  = 'source-uuid';
    private const string OPT_LIMIT        = 'limit';
    private const string OPT_DRY_RUN      = 'dry-run';
    public const string LOCK_RESOURCE     = 'command:source:download';

    public function __construct(
        private readonly QueryBusComponentInterface $queryBus,
        private readonly CommandBusComponentInterface $commandBus,
        private readonly LockFactory $lockFactory,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption(self::OPT_PROJECT_UUID, 'p', InputOption::VALUE_REQUIRED, 'Идентификатор проекта (UUID)')
            ->addOption(self::OPT_SOURCE_UUID,  's', InputOption::VALUE_REQUIRED, 'Идентификатор источника (UUID)')
            ->addOption(self::OPT_LIMIT,        'l', InputOption::VALUE_REQUIRED, 'Ограничение количества за один запуск')
            ->addOption(self::OPT_DRY_RUN,      'd', InputOption::VALUE_NONE,     'Показать, что будет сделано, без выполнения');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $lock = $this->lockFactory->createLock(self::LOCK_RESOURCE);

        if (!$lock->acquire()) {
            $io->warning(sprintf('Команда "%s" уже выполняется. Пропускаем.', $this->getName() ?? static::class));
            return Command::SUCCESS;
        }

        try {
            $projectUuid = $this->parseUuid($input->getOption(self::OPT_PROJECT_UUID));
            $sourceUuid  = $this->parseUuid($input->getOption(self::OPT_SOURCE_UUID));
            $limit       = $this->parsePositiveInt($input->getOption(self::OPT_LIMIT));
            $dryRun      = (bool)$input->getOption(self::OPT_DRY_RUN);

            $result = $this->queryBus->query(new GetForDownloadQuery(
                sourceUuid: $sourceUuid,
                projectUuid: $projectUuid,
                pagination: $limit === null ? null : new PaginationDto($limit),
            ));

            $io->section(sprintf('Планируем загрузить источников: %u', $result->total));

            $i = 0;
            foreach ($result->items as $item) {
                $i++;
                $io->text(sprintf('%u) %s — %s', $i, $item->uuid->toString(), $item->title));

                if ($dryRun) {
                    continue;
                }

                try {
                    $this->commandBus->execute(new ApplicationDownloadCommand($item->uuid));
                } catch (Exception $e) {
                    $io->error($e->getMessage());
                }
            }

            return Command::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function parseUuid(null|string $raw): ?Uuid
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        return Uuid::fromString($raw);
    }

    private function parsePositiveInt(null|string $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $val = (int)$raw;
        if ($val <= 0) {
            throw new \InvalidArgumentException('Параметр --limit должен быть положительным целым.');
        }
        return $val;
    }
}
```

## Оптимизации производительности

Для длительных операций и обработки больших объёмов данных применяются следующие подходы.

### Батч-обработка

Обработка данных порциями через пагинацию:

```php
$limit = $this->parsePositiveInt($input->getOption(self::OPT_LIMIT));
$offset = 0;

do {
    $result = $this->queryBus->query(new GetBatchQuery(
        pagination: new PaginationDto(limit: $limit ?? 100, offset: $offset),
    ));

    foreach ($result->items as $item) {
        $this->commandBus->execute(new ProcessCommand($item->uuid));
    }

    $offset += $limit ?? 100;
} while (count($result->items) > 0);
```

### Паузы между обработками

Для снижения нагрузки на внешние сервисы:

```php
foreach ($result->items as $item) {
    $this->commandBus->execute(new ProcessCommand($item->uuid));
    usleep(100000); // 100ms пауза
}
```

### Обработка исключений

Локальная обработка с продолжением цикла:

```php
foreach ($result->items as $item) {
    try {
        $this->commandBus->execute(new ProcessCommand($item->uuid));
        $io->text(sprintf('✓ %s', $item->uuid->toString()));
    } catch (Exception $e) {
        $io->error(sprintf('✗ %s: %s', $item->uuid->toString(), $e->getMessage()));
        // Продолжаем обработку следующего элемента
    }
}
```

### Режим dry-run

Предпросмотр без выполнения:

```php
$dryRun = (bool)$input->getOption(self::OPT_DRY_RUN);

foreach ($result->items as $item) {
    if ($dryRun) {
        $io->text(sprintf('[DRY-RUN] Would process: %s', $item->uuid->toString()));
        continue;
    }
    $this->commandBus->execute(new ProcessCommand($item->uuid));
}
```

## Чек-лист для ревью

- [ ] Файл расположен в каталоге Presentation и объявлен `final`, используется `#[AsCommand]`.
- [ ] Нет публичных методов, кроме API `Command`; бизнес-логика отсутствует.
- [ ] Внедрены только зависимости Presentation/Application; нет прямых репозиториев/ORM/Infrastructure.
- [ ] Валидация аргументов/опций выполнена до вызова UseCase (UUID/диапазоны/required).
- [ ] Конкурентный запуск защищён `LockFactory` (если применимо).
- [ ] Вывод через `SymfonyStyle`, понятные сообщения; предусмотрен `--dry-run` (если уместно).
- [ ] Возвращаются корректные коды завершения (`SUCCESS`/`FAILURE`).
- [ ] Обработка ошибок локальная (try/catch), фатальные ошибки не скрываются.
- [ ] Команда подходит для cron: детерминированный вывод, без лишнего шума.
- [ ] Для длительных операций применяются батч-обработка и паузы.
