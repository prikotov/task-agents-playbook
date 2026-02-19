<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\RunHealthChecks;

use Common\Component\Persistence\PersistenceManagerInterface;
use Common\Module\Health\Application\Service\HealthChecker\HealthCheckerRegistryService;
use Common\Module\Health\Domain\Enum\ServiceCategoryEnum;
use Common\Module\Health\Domain\Repository\ServiceStatus\Criteria\ServiceStatusFindCriteria;
use Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusRepositoryInterface;
use InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RunHealthChecksCommandHandler
{
    public function __construct(
        private ServiceStatusRepositoryInterface $repository,
        private HealthCheckerRegistryService $registry,
        private PersistenceManagerInterface $persistenceManager,
    ) {
    }

    public function __invoke(RunHealthChecksCommand $command): void
    {
        $criteria = new ServiceStatusFindCriteria();

        if ($command->category !== null) {
            $category = ServiceCategoryEnum::tryFrom($command->category);
            if ($category === null) {
                throw new InvalidArgumentException(
                    sprintf('Invalid category "%s". Valid values: %s', $command->category, implode(', ', array_column(ServiceCategoryEnum::cases(), 'value')))
                );
            }
            $criteria->setCategory($category);
        }

        if ($command->serviceName !== null) {
            $criteria->setName($command->serviceName);
        }

        $serviceModels = $this->repository->getByCriteria($criteria);

        foreach ($serviceModels as $model) {
            $checker = $this->registry->getChecker($model->getName());
            if ($checker !== null) {
                $result = $checker->check();
                $model->setStatus($result->status);
                $model->setMessage($result->message);
                $model->setLastCheckAt($result->checkedAt);
                $model->setResponseTimeMs($result->responseTimeMs);
                $this->persistenceManager->persist($model);
            }
        }

        $this->persistenceManager->flush();
    }
}
