<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Query\GetSystemHealth;

use Common\Module\Health\Application\Dto\ServiceHealthDto;
use Common\Module\Health\Application\Dto\SystemHealthDto;
use Common\Module\Health\Application\Enum\ServiceCategoryEnum as AppServiceCategoryEnum;
use Common\Module\Health\Application\Enum\ServiceStatusEnum as AppServiceStatusEnum;
use Common\Module\Health\Domain\Enum\ServiceCategoryEnum;
use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use Common\Module\Health\Domain\Repository\ServiceStatus\Criteria\ServiceStatusFindCriteria;
use Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GetSystemHealthQueryHandler
{
    public function __construct(
        private ServiceStatusRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetSystemHealthQuery $query): SystemHealthDto
    {
        $criteria = new ServiceStatusFindCriteria();

        if ($query->category !== null) {
            $category = ServiceCategoryEnum::tryFrom($query->category);
            if ($category === null) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid category "%s". Valid values: %s',
                        $query->category,
                        implode(', ', array_column(ServiceCategoryEnum::cases(), 'value'))
                    )
                );
            }
            $criteria->setCategory($category);
        }

        $serviceModels = $this->repository->getByCriteria($criteria);

        $services = [];
        $counts = [
            ServiceStatusEnum::operational->value => 0,
            ServiceStatusEnum::degraded->value => 0,
            ServiceStatusEnum::outage->value => 0,
            ServiceStatusEnum::maintenance->value => 0,
        ];

        foreach ($serviceModels as $model) {
            $dto = new ServiceHealthDto(
                name: $model->getName(),
                status: AppServiceStatusEnum::from($model->getStatus()->value),
                category: AppServiceCategoryEnum::from($model->getCategory()->value),
                lastCheckAt: $model->getLastCheckAt(),
                message: $model->getMessage(),
                responseTimeMs: $model->getResponseTimeMs(),
            );
            $services[] = $dto;
            $counts[$model->getStatus()->value]++;
        }

        $overallStatus = $this->calculateOverallStatus($counts);

        return new SystemHealthDto(
            overallStatus: $overallStatus,
            services: $services,
            checkedAt: new DateTimeImmutable(),
            totalServices: count($services),
            operationalCount: $counts[ServiceStatusEnum::operational->value],
            degradedCount: $counts[ServiceStatusEnum::degraded->value],
            outageCount: $counts[ServiceStatusEnum::outage->value],
            maintenanceCount: $counts[ServiceStatusEnum::maintenance->value],
        );
    }

    /**
     * @param array<string, int> $counts
     */
    private function calculateOverallStatus(array $counts): AppServiceStatusEnum
    {
        if ($counts[ServiceStatusEnum::outage->value] > 0) {
            return AppServiceStatusEnum::outage;
        }

        if ($counts[ServiceStatusEnum::degraded->value] > 0) {
            return AppServiceStatusEnum::degraded;
        }

        if ($counts[ServiceStatusEnum::maintenance->value] > 0) {
            return AppServiceStatusEnum::maintenance;
        }

        return AppServiceStatusEnum::operational;
    }
}
