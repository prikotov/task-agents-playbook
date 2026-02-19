<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\Incident\CreateIncident;

use Common\Component\Event\EventBusInterface;
use Common\Component\Persistence\PersistenceManagerInterface;
use Common\Module\Health\Application\Event\Incident\CreatedEvent;
use Common\Module\Health\Domain\Entity\IncidentModel;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class CreateIncidentCommandHandler
{
    public function __construct(
        private PersistenceManagerInterface $persistenceManager,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(CreateIncidentCommand $command): Uuid
    {
        // Валидация выполняется на уровне FormType через Symfony Constraints
        // Создание через конструктор по конвенции проекта
        $incident = new IncidentModel(
            title: $command->title,
            description: $command->description,
            severity: $command->severity,
            affectedServiceNames: $command->affectedServiceNames,
        );

        $this->persistenceManager->persist($incident);
        $this->persistenceManager->flush();

        $this->eventBus->dispatch(new CreatedEvent(
            incidentUuid: $incident->getUuid(),
            title: $incident->getTitle(),
            severity: $incident->getSeverity(),
        ));

        return $incident->getUuid();
    }
}
