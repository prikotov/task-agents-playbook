<?php

declare(strict_types=1);

namespace Web\Module\Health\Map;

use Common\Module\Health\Application\Enum\ServiceStatusEnum;
use OutOfBoundsException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Map-класс для текстового представления статусов сервисов.
 */
final class ServiceStatusLabelMap
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            ServiceStatusEnum::operational->value => $this->translator->trans('health.dashboard.stats.operational', domain: 'messages'),
            ServiceStatusEnum::degraded->value => $this->translator->trans('health.dashboard.stats.degraded', domain: 'messages'),
            ServiceStatusEnum::outage->value => $this->translator->trans('health.dashboard.stats.outage', domain: 'messages'),
            ServiceStatusEnum::maintenance->value => $this->translator->trans('health.dashboard.stats.maintenance', domain: 'messages'),
        ];
    }

    /**
     * @throws OutOfBoundsException
     */
    public function getAssociationByValue(ServiceStatusEnum $status): string
    {
        return $this->statusLabels()[$status->value]
            ?? throw new OutOfBoundsException("No bounded association for value '{$status->value}'");
    }
}
