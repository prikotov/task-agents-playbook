<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Query\Incident\GetIncident;

use Common\Module\Health\Application\Dto\IncidentDto;
use Common\Module\Health\Application\Mapper\IncidentDtoMapper;
use Common\Module\Health\Domain\Repository\Incident\IncidentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetIncidentQueryHandler
{
    public function __construct(
        private IncidentRepositoryInterface $repository,
        private IncidentDtoMapper $mapper,
    ) {
    }

    public function __invoke(GetIncidentQuery $query): IncidentDto
    {
        $incident = $this->repository->getById(uuid: $query->uuid);

        return $this->mapper->map($incident);
    }
}
