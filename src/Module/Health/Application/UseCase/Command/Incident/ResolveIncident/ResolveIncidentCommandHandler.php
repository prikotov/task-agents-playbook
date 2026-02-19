<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\Incident\ResolveIncident;

use Common\Component\Event\EventBusInterface;
use Common\Component\Persistence\PersistenceManagerInterface;
use Common\Module\Health\Application\Event\Incident\ResolvedEvent;
use Common\Module\Health\Domain\Repository\Incident\IncidentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ResolveIncidentCommandHandler
{
    public function __construct(
        private IncidentRepositoryInterface $repository,
        private PersistenceManagerInterface $persistenceManager,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(ResolveIncidentCommand $command): void
    {
        $incident = $this->repository->getById(uuid: $command->uuid);
        $incident->resolve();

        $this->persistenceManager->persist($incident);
        $this->persistenceManager->flush();

        $this->eventBus->dispatch(new ResolvedEvent(
            incidentUuid: $incident->getUuid(),
            title: $incident->getTitle(),
        ));
    }
}
