# Application слой

**Application слой** — слой приложения, который оркестрирует выполнение бизнес-операций, координирует взаимодействие между доменной логикой, инфраструктурой и внешними интерфейсами. Это уровень, где реализуются сценарии использования (use cases) приложения.

Application слой не содержит бизнес-логики — он лишь координирует выполнение операций, делегируя бизнес-правила в Domain слой, а технические детали — в Infrastructure слой.

## Назначение слоя Application

Application слой выполняет следующие функции:

- **Оркестрация бизнес-операций**: координирует выполнение сценариев использования, объединяя несколько операций домена в единый процесс
- **Преобразование данных**: маппинг DTO между слоями (Presentation → Application → Domain и обратно)
- **Управление транзакциями**: определяет границы транзакций для команд, изменяющих состояние
- **Валидация входных данных**: проверка корректности входных данных перед передачей в домен
- **Обработка ошибок**: преобразование исключений домена и инфраструктуры в подходящие для Presentation слоя

### Разделение ответственности между Application и Domain слоями

| Аспект | Domain слой | Application слой |
|--------|-------------|------------------|
| Бизнес-логика | Содержит и реализует | Не содержит, только вызывает |
| Инварианты | Проверяет внутри сущностей | Проверяет на уровне валидации входных данных |
| Транзакции | Не управляет | Управляет границами транзакций |
| Внешние сервисы | Не зависит | Координирует через абстракции |
| DTO | Не использует | Использует для передачи данных между слоями |

### Почему Application слой не содержит бизнес-логику

Бизнес-логика должна быть сосредоточена в Domain слое по следующим причинам:

1. **Переиспользование**: бизнес-правила, реализованные в домене, могут использоваться из разных Use Case-ов
2. **Тестируемость**: доменную логику проще тестировать в изоляции от оркестрации
3. **Чистота архитектуры**: разделение ответственности упрощает понимание и поддержку кода
4. **DDD принципы**: Domain-Driven Design требует концентрации бизнес-знаний в домене

## Компоненты слоя Application

### Use Cases

Use Case (сценарий использования) — реализует конкретное действие, которое может выполнить пользователь или система. Use Case-ы делятся на два типа:

- **Command** — изменяет состояние приложения (создание, обновление, удаление)
- **Query** — возвращает данные из приложения без изменения состояния

Подробнее: [Use Cases](use_case.md) | [Symfony Messenger](https://symfony.com/doc/current/messenger.html)

### Command Handlers

**Command Handler** — обработчик команды, который реализует изменение состояния приложения. Хендлер:

- Принимает Command (DTO с входными данными)
- Оркестрирует вызовы доменных сервисов, репозиториев и сущностей
- Управляет транзакцией (persist/flush)
- Диспетчеризирует доменные события
- Возвращает результат (void, идентификатор или DTO)

**Правила реализации:**

- Хендлер должен завершиться успешно или выбросить исключение
- Не может прокидывать исключения внешних зависимостей напрямую — оборачивать их в `Common\Exception\{ExceptionName}`
- Выполняет только одну логическую транзакцию
- Запрещено вызывать другие Use Case внутри CommandHandler

Подробнее: [Command и CommandHandler](command_handler.md)

### Query Handlers

**Query Handler** — обработчик запроса, который возвращает данные из приложения. Хендлер:

- Принимает Query (DTO с параметрами запроса)
- Выполняет чтение данных через репозитории
- Маппит доменные модели в DTO
- Возвращает результат (DTO, Enum или скалярное значение)

**Правила реализации:**

- Query Handler не должен изменять состояние приложения
- Запрещено вызывать другие Use Case внутри QueryHandler
- Название запроса должно начинаться с глагола (например: `GetCustomerQuery`)
- Класс обработчика должен иметь постфикс `QueryHandler`

Подробнее: [Query и Query Handler](query_handler.md)

### DTOs (Data Transfer Objects)

**DTO** — простой неизменяемый объект для передачи данных между слоями. В Application слое DTO используются для:

- Входных данных Command/Query
- Выходных данных Query Handler
- Результатов выполнения Command Handler

**Правила использования:**

- Только данные, без бизнес-логики
- Неизменяемость: `final readonly class`
- Строгая типизация: скаляры, Enum, Uuid, DateTimeImmutable, другие DTO
- Никаких сервисов внутри DTO
- Конструктор не содержит логики и преобразований

Подробнее: [DTO](../guides/dto.md)

### Mappers

**Mapper** — класс, выполняющий преобразование данных между слоями. В Application слое мапперы используются для:

- Преобразования доменных моделей в DTO (для ответов)
- Преобразования DTO в доменные VO или примитивы (для запросов)
- Преобразования Enum между слоями (Application ↔ Domain)

**Правила именования:**

- `{Source}To{Target}Mapper` — например: `ProjectDtoMapper`, `ApplicationToDomainProjectStatusMapper`
- Расположение: `Common\Module\{ModuleName}\Application\Mapper\`

## Правила взаимодействия

### Application → Domain

Application слой взаимодействует с Domain слоем только через публичные интерфейсы:

- **Репозитории**: через интерфейсы из `Domain\Repository\*`
- **Сущности**: через методы изменения состояния
- **Спецификации**: через интерфейсы из `Domain\Specification\*`
- **Сервисы домена**: через интерфейсы из `Domain\Service\*`

**Пример взаимодействия:**

```php
// Application слой (CommandHandler)
public function __invoke(CreateCommand $command): Uuid
{
    // 1. Получаем сущность через репозиторий (Domain)
    $user = $this->userRepository->getById(uuid: $command->creatorUuid);
    
    // 2. Создаём новую сущность (Domain)
    $project = new ProjectModel(
        ProjectStatusEnum::new,
        $command->title,
        $command->description,
        $user,
        null
    );
    
    // 3. Добавляем связанную сущность (Domain)
    $owner = $this->userRepository->getById(uuid: $ownerUuid);
    $project->addProjectUser(
        new ProjectUserModel($project, $owner, ProjectUserTypeEnum::owner),
    );
    
    // 4. Сохраняем через репозиторий (Infrastructure, управляемый в Application)
    $this->persistenceManager->persist($project);
    $this->persistenceManager->flush();
    
    return $project->getUuid();
}
```

### Application → Infrastructure

Application слой взаимодействует с Infrastructure слоем через абстракции:

- **Репозитории**: через интерфейсы из `Domain\Repository\*` (реализация в Infrastructure)
- **Событийная шина**: через `EventBusInterface`
- **Менеджер персистентности**: через `PersistenceManagerInterface`
- **Внешние сервисы**: через компоненты из `Infrastructure\Component\*` или `Integration\Component\*`

**Пример взаимодействия:**

```php
// Application слой (CommandHandler)
public function __invoke(CreateCommand $command): Uuid
{
    // Используем менеджер персистентности (Infrastructure абстракция)
    $this->persistenceManager->persist($project);
    $this->persistenceManager->flush();
    
    // Диспетчеризируем событие (Infrastructure абстракция)
    $this->eventBus->dispatch(new CreatedEvent(
        projectUuid: $project->getUuid(),
        projectTitle: $project->getTitle(),
        creatorUuid: $creator->getUuid(),
    ));
    
    return $project->getUuid();
}
```

### Запрет на прямые вызовы из Presentation в Domain

Presentation слой (контроллеры, консольные команды) не должен напрямую обращаться к Domain слою. Все взаимодействия должны проходить через Application слой:

```
Presentation → Application → Domain
                ↓
            Infrastructure
```

**Почему это важно:**

- Изоляция доменной логики от внешних интерфейсов
- Единая точка валидации и трансформации данных
- Возможность переиспользования Use Case-ов из разных точек входа
- Упрощение тестирования и поддержки

## Примеры кода

### Пример Command/Handler пары

**Command:**

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Application\UseCase\Command\Project\Create;

use Common\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @implements CommandInterface<Uuid>
 */
final readonly class CreateCommand implements CommandInterface
{
    public function __construct(
        public string $title,
        public string $description,
        public Uuid $creatorUuid,
        public ?Uuid $ownerUuid = null,
    ) {
    }
}
```

**CommandHandler:**

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Application\UseCase\Command\Project\Create;

use Common\Component\Event\EventBusInterface;
use Common\Component\Persistence\PersistenceManagerInterface;
use Common\Exception\ConflictException;
use Common\Exception\NotFoundExceptionInterface;
use Common\Module\Project\Application\Event\Project\CreatedEvent;
use Common\Module\Project\Domain\Enum\ProjectStatusEnum;
use Common\Module\Project\Domain\Enum\ProjectUserTypeEnum;
use Common\Module\Project\Domain\Repository\Project\Criteria\ProjectFindCriteria;
use Common\Module\Project\Domain\Repository\Project\ProjectRepositoryInterface;
use Common\Module\Project\Domain\Entity\ProjectModel;
use Common\Module\Project\Domain\Entity\ProjectUserModel;
use Common\Module\User\Domain\Repository\User\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler] // Подробности: https://symfony.com/doc/current/messenger.html#handling-messages-synchronously-or-asynchronously
final readonly class CreateCommandHandler
{
    public function __construct(
        private PersistenceManagerInterface $persistenceManager,
        private ProjectRepositoryInterface $projectRepository,
        private UserRepositoryInterface $userRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ConflictException
     */
    public function __invoke(CreateCommand $command): Uuid
    {
        $ownerUuid = $command->ownerUuid ?: $command->creatorUuid;
        
        // Проверка дубликатов через репозиторий (Domain)
        $criteria = new ProjectFindCriteria(
            userUuid: $ownerUuid,
            userRole: ProjectUserTypeEnum::owner,
            title: $command->title,
        );
        if ($this->projectRepository->exists($criteria)) {
            throw new ConflictException(sprintf(
                "Project with title '%s' already exists for user %s",
                $command->title,
                $ownerUuid->toString(),
            ));
        }
        
        // Получение пользователя через репозиторий (Domain)
        $creator = $this->userRepository->getById(uuid: $command->creatorUuid);
        
        // Создание сущности (Domain)
        $project = new ProjectModel(
            ProjectStatusEnum::new,
            $command->title,
            $command->description,
            $creator,
            null
        );

        $owner = $this->userRepository->getById(uuid: $ownerUuid);
        $project->addProjectUser(
            new ProjectUserModel($project, $owner, ProjectUserTypeEnum::owner),
        );

        // Сохранение через менеджер персистентности (Infrastructure)
        $this->persistenceManager->persist($project);
        $this->persistenceManager->flush();

        // Диспетчеризация события (Infrastructure)
        $this->eventBus->dispatch(new CreatedEvent(
            projectUuid: $project->getUuid(),
            projectTitle: $project->getTitle(),
            creatorUuid: $creator->getUuid(),
        ));

        return $project->getUuid();
    }
}
```

### Пример Query/Handler пары

**Query:**

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Application\UseCase\Query\Project\Find;

use Common\Application\Dto\PaginationDto;
use Common\Application\Dto\SortDto;
use Common\Application\Query\QueryInterface;
use Common\Module\Project\Application\Enum\ProjectStatusEnum;
use Symfony\Component\Uid\Uuid;

/**
 * @implements QueryInterface<FindResultDto>
 */
final readonly class FindQuery implements QueryInterface
{
    public function __construct(
        public ?ProjectStatusEnum $status = null,
        public ?Uuid $userUuid = null,
        public ?Uuid $sourceUuid = null,
        public ?string $search = null,
        public ?PaginationDto $pagination = null,
        public ?SortDto $sort = null,
    ) {
    }
}
```

**QueryHandler:**

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Application\UseCase\Query\Project\Find;

use Common\Application\Dto\SortDto;
use Common\Application\Enum\SortDirectionEnum;
use Common\Application\Mapper\SortDtoToOrderMapper;
use Common\Module\Project\Application\Mapper\ApplicationToDomainProjectStatusMapper;
use Common\Module\Project\Application\Mapper\ProjectDtoMapper;
use Common\Module\Project\Domain\Repository\Project\Criteria\ProjectFindCriteria;
use Common\Module\Project\Domain\Repository\Project\ProjectRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler] // Подробности: https://symfony.com/doc/current/messenger.html#handling-messages-synchronously-or-asynchronously
final readonly class FindQueryHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private SortDtoToOrderMapper $sortDtoToOrderMapper,
        private ProjectDtoMapper $projectDtoMapper,
        private ApplicationToDomainProjectStatusMapper $applicationToDomainProjectStatusMapper,
    ) {
    }

    public function __invoke(FindQuery $query): FindResultDto
    {
        // Маппинг Enum из Application в Domain
        $projectStatusEnum = $this->applicationToDomainProjectStatusMapper->map($query->status);

        // Создание критериев запроса (Domain)
        $criteria = new ProjectFindCriteria(
            status: $projectStatusEnum,
            userUuid: $query->userUuid,
            sourceUuid: $query->sourceUuid,
            search: $query->search,
        );
        
        // Получение общего количества через репозиторий (Domain)
        $total = $this->projectRepository->getCountByCriteria($criteria);

        if ($query->pagination !== null) {
            $criteria->setLimit($query->pagination->limit);
            $criteria->setOffset($query->pagination->offset);
        }

        $criteria->setSort($this->sortDtoToOrderMapper->map(
            $query->sort ?? new SortDto(['title' => SortDirectionEnum::asc]),
        ));

        // Получение данных через репозиторий (Domain)
        $result = $this->projectRepository->getByCriteria($criteria);
        
        // Маппинг доменных моделей в DTO (Application)
        $items = [];
        foreach ($result as $project) {
            $items[] = $this->projectDtoMapper->map($project);
        }

        return new FindResultDto($items, $total);
    }
}
```

### Пример DTO с маппером

**DTO:**

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Application\Dto;

use Common\Module\Project\Application\Enum\ProjectStatusEnum;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class ProjectDto
{
    public function __construct(
        public int $id,
        public Uuid $uuid,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
        public ProjectStatusEnum $status,
        public string $title,
        public string $description,
        public ?CreatorDto $creator,
    ) {
    }
}
```

**Mapper:**

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Application\Mapper;

use Common\Module\Project\Application\Dto\CreatorDto;
use Common\Module\Project\Application\Dto\ProjectDto;
use Common\Module\Project\Application\Enum\ProjectStatusEnum as ApplicationProjectStatusEnum;
use Common\Module\Project\Domain\Entity\ProjectModel;
use Common\Module\Project\Domain\Enum\ProjectStatusEnum as DomainProjectStatusEnum;

final readonly class ProjectDtoMapper
{
    public function __construct(
        private DomainToApplicationProjectStatusMapper $domainToApplicationProjectStatusMapper,
    ) {
    }

    public function map(ProjectModel $project): ProjectDto
    {
        return new ProjectDto(
            id: $project->getId(),
            uuid: $project->getUuid(),
            createdAt: $project->getCreatedAt(),
            updatedAt: $project->getUpdatedAt(),
            status: $this->domainToApplicationProjectStatusMapper->map($project->getStatus()),
            title: $project->getTitle(),
            description: $project->getDescription(),
            creator: $project->getCreator() !== null ? new CreatorDto(
                uuid: $project->getCreator()->getUuid(),
                email: $project->getCreator()->getEmail(),
            ) : null,
        );
    }
}
```

## Расположение

Application слой располагается в:

```
src/Module/{ModuleName}/Application/
├── UseCase/
│   ├── Command/
│   │   └── {CommandGroup}/
│   │       └── {CommandName}/
│   │           ├── {CommandName}Command.php
│   │           └── {CommandName}CommandHandler.php
│   └── Query/
│       └── {QueryGroup}/
│           └── {QueryName}/
│               ├── {QueryName}Query.php
│               ├── {QueryName}QueryHandler.php
│               └── {QueryName}QueryResultDto.php
├── Dto/
│   └── {Name}Dto.php
├── Enum/
│   └── {Name}Enum.php
├── Event/
│   └── {EventGroup}/
│       └── {EventName}Event.php
├── Mapper/
│   └── {Name}Mapper.php
└── Service/
    └── {Name}Service.php
```

## Чек-лист для ревью

- [ ] Use Case (Command/Query) не содержит бизнес-логику, только оркестрацию
- [ ] Command Handler выполняет только одну логическую транзакцию
- [ ] Query Handler не изменяет состояние приложения
- [ ] DTO — `final readonly class`, без бизнес-логики и сервисов
- [ ] Взаимодействие с Domain слоем только через публичные интерфейсы
- [ ] Взаимодействие с Infrastructure слоем через абстракции
- [ ] Исключения внешних зависимостей оборачиваются в `Common\Exception\{ExceptionName}`
- [ ] Мапперы расположены в `Application\Mapper\*`
- [ ] Enum-ы Application слоя не смешиваются с Domain Enum-ами (есть мапперы)
- [ ] Транзакция управляется в Command Handler через `persist/flush`
