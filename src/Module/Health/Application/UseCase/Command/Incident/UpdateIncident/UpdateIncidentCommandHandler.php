<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\Incident\UpdateIncident;

use Common\Component\Event\EventBusInterface;
use Common\Component\Persistence\PersistenceManagerInterface;
use Common\Module\Health\Application\Event\Incident\UpdatedEvent;
use Common\Module\Health\Domain\Repository\Incident\IncidentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateIncidentCommandHandler
{
    public function __construct(
        private IncidentRepositoryInterface $repository,
        private PersistenceManagerInterface $persistenceManager,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(UpdateIncidentCommand $command): void
    {
        $incident = $this->repository->getById(uuid: $command->uuid);

        if ($command->title !== null) {
            $incident->setTitle($command->title);
        }

        if ($command->description !== null) {
            $incident->setDescription($command->description);
        }

        if ($command->status !== null) {
            $incident->updateStatus($command->status);
        }

        if ($command->severity !== null) {
            $incident->setSeverity($command->severity);
        }

        if ($command->affectedServiceNames !== null) {
            $incident->setAffectedServiceNames($command->affectedServiceNames);
        }

        $this->persistenceManager->persist($incident);
        $this->persistenceManager->flush();

        $this->eventBus->dispatch(new UpdatedEvent(
            incidentUuid: $incident->getUuid(),
            title: $incident->getTitle(),
        ));
    }
}
