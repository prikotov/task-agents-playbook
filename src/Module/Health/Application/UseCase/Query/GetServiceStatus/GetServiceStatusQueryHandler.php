<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Query\GetServiceStatus;

use Common\Exception\NotFoundException;
use Common\Module\Health\Application\Dto\ServiceHealthDto;
use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use Common\Module\Health\Application\Enum\ServiceStatusEnum;
use Common\Module\Health\Domain\Repository\ServiceStatus\Criteria\ServiceStatusFindCriteria;
use Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetServiceStatusQueryHandler
{
    public function __construct(
        private ServiceStatusRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetServiceStatusQuery $query): ServiceHealthDto
    {
        $criteria = new ServiceStatusFindCriteria(name: $query->serviceName);
        $model = $this->repository->getOneByCriteria($criteria);

        if ($model === null) {
            throw new NotFoundException(
                message: sprintf('Service "%s" not found', $query->serviceName),
            );
        }

        return new ServiceHealthDto(
            name: $model->getName(),
            status: ServiceStatusEnum::from($model->getStatus()->value),
            category: ServiceCategoryEnum::from($model->getCategory()->value),
            lastCheckAt: $model->getLastCheckAt(),
            message: $model->getMessage(),
            responseTimeMs: $model->getResponseTimeMs(),
        );
    }
}
