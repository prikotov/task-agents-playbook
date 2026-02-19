<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\UpdateServiceStatus;

use Common\Component\Persistence\PersistenceManagerInterface;
use Common\Exception\NotFoundException;
use Common\Module\Health\Domain\Repository\ServiceStatus\Criteria\ServiceStatusFindCriteria;
use Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateServiceStatusCommandHandler
{
    public function __construct(
        private ServiceStatusRepositoryInterface $repository,
        private PersistenceManagerInterface $persistenceManager,
    ) {
    }

    public function __invoke(UpdateServiceStatusCommand $command): void
    {
        $criteria = new ServiceStatusFindCriteria(name: $command->serviceName);
        $model = $this->repository->getOneByCriteria($criteria);

        if ($model === null) {
            throw new NotFoundException(
                message: sprintf('Service "%s" not found', $command->serviceName),
            );
        }

        $model->setStatus($command->status);
        $model->setMessage($command->message);
        $model->setResponseTimeMs($command->responseTimeMs);
        $model->setLastCheckAt(new DateTimeImmutable());

        $this->persistenceManager->persist($model);
        $this->persistenceManager->flush();
    }
}
