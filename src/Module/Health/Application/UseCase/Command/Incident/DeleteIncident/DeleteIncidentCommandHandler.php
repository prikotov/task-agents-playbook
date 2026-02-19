<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\Incident\DeleteIncident;

use Common\Component\Event\EventBusInterface;
use Common\Component\Persistence\PersistenceManagerInterface;
use Common\Module\Health\Application\Event\Incident\DeletedEvent;
use Common\Module\Health\Domain\Repository\Incident\IncidentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteIncidentCommandHandler
{
    public function __construct(
        private IncidentRepositoryInterface $repository,
        private PersistenceManagerInterface $persistenceManager,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(DeleteIncidentCommand $command): void
    {
        $incident = $this->repository->getById(uuid: $command->uuid);

        // Сохраняем данные для события до удаления
        $incidentUuid = $incident->getUuid();
        $incidentTitle = $incident->getTitle();

        $this->persistenceManager->remove($incident);
        $this->persistenceManager->flush();

        $this->eventBus->dispatch(new DeletedEvent(
            incidentUuid: $incidentUuid,
            title: $incidentTitle,
        ));
    }
}
