# Command и CommandHandler

**Команда (Command)** — разновидность [Use Case](use_cases.md), описывающая намерение изменить состояние приложения (
модуля).
Представляет собой DTO, передаваемое в Command Handler.

**Обработчик команды (Command Handler)** — реализует изменение состояния модуля, оркестрируя взаимодействие с доменной
логикой, сервисами и инфраструктурой.

## Где размещаются

- [Application](application_layer.md).

```php
Common\Module\{ModuleName}\Application\UseCase\Command\{CommandGroup}\{CommandName}\{CommandName}Command
Common\Module\{ModuleName}\Application\UseCase\Command\{CommandGroup}\{CommandName}\{CommandName}CommandHandler
```

## Как создаем

- Создаётся только для изменения состояния модуля **по внешнему бизнес-запросу** (например: от контроллера, очереди,
  cron-задачи).
- Command — это [DTO](../../guides/dto.md), реализующее интерфейс `CommandInterface<ReturnType>`. Оно описывает входные данные,
  необходимые для выполнения бизнес-действия в Command Handler.
- Допустимы лёгкие guard-проверки в конструкторе команды (например, проверка batch size > 0), чтобы не строить
  заведомо некорректный DTO. Основная бизнес-валидация остаётся на Command Handler/Domain-слое.
- Command Handler:
    - должен завершиться успешно или выбросить исключение.
    - не может прокидывать исключения внешних зависимостей напрямую — оборачивать их в
      `Common\Exception\{ExceptionName}`.
    - возвращает `void` или идентификатор созданной сущности (например, `int`, `Uuid`). DTO допустим только если нужно
      вернуть несколько связанных идентификаторов (например, пару `int id` + `uuid` через `Common\Application\Dto\IdDto`).
    - **события должны dispatch'ся ПОСЛЕ `flush()`**, когда данные уже записаны в БД.
      Подробнее: [Events & Transactions — взаимодействие событий и транзакций БД](../../../architecture/events/transactions.md).
- Command Handler должен **выполнять только одну логическую транзакцию**.
- **Запрещено** вызывать другие UseCase внутри CommandHandler, включая вызов через `__invoke()` другого `*Handler` и запуск через `CommandBus`/`QueryBus`.

## Пример команды

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

## Пример обработчика команды

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

#[AsMessageHandler]
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
        $creator = $this->userRepository->getById(uuid: $command->creatorUuid);
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

        $this->persistenceManager->persist($project);
        $this->persistenceManager->flush();

        $this->eventBus->dispatch(new CreatedEvent(
            projectUuid: $project->getUuid(),
            projectTitle: $project->getTitle(),
            creatorUuid: $creator->getUuid(),
        ));

        return $project->getUuid();
    }
}
```

## Пример вызова команды из контроллера

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\Controller\Project;

use Common\Application\Component\CommandBus\CommandBusComponentInterface;
use Common\Module\Project\Application\UseCase\Command\Project\Create\CreateCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Web\Module\Project\Controller\Project\Enum\ProjectActionEnum;
use Web\Module\Project\Form\Project\CreateFormModel;
use Web\Module\Project\Form\Project\CreateFormType;
use Web\Module\Project\Route\ProjectRoute;
use Web\Security\UserInterface;

#[Route(
    path: ProjectRoute::CREATE_PATH,
    name: ProjectRoute::CREATE,
    methods: [Request::METHOD_GET, Request::METHOD_POST],
)]
#[AsController]
final class CreateController extends AbstractController
{
    public function __construct(
        private readonly CommandBusComponentInterface $commandBus,
    ) {
    }

    public function __invoke(
        Request $request,
        #[CurrentUser]
        UserInterface $currentUser,
    ): Response {
        if (!$this->isGranted(ProjectActionEnum::create->value, ['userUuid' => $currentUser->getUuid()])) {
            throw new AccessDeniedException('Access Denied.');
        }

        $formModel = new CreateFormModel();

        $form = $this->createForm(CreateFormType::class, $formModel);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CreateFormModel $formModel */
            $formModel = $form->getData();
            $id = $this->commandBus->execute(new CreateCommand(
                $formModel->getTitle(),
                (string)$formModel->getDescription(),
                $currentUser->getUuid(),
            ));

            return $this->redirectToRoute(ProjectRoute::VIEW, [
                'uuid' => $id->uuid,
            ]);
        }

        return $this->render('@web.project/project/create.html.twig', [
            'form' => $form,
        ]);
    }
}
```

> 💡 В продакшн-коде рекомендуется использовать CommandBus для доставки команд, особенно при использовании Symfony
> Messenger и очередей. Прямой вызов CommandHandler допустим для unit-тестов или простых MVP-прототипов.
