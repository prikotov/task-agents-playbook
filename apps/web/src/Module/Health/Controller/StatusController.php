<?php

declare(strict_types=1);

namespace Web\Module\Health\Controller;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Application\Dto\PaginationDto;
use Common\Application\Dto\SortDto;
use Common\Application\Enum\SortDirectionEnum;
use Common\Module\Health\Application\Dto\ServiceHealthDto;
use Common\Module\Health\Application\Dto\SystemHealthDto;
use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use Common\Module\Health\Application\UseCase\Query\GetSystemHealth\GetSystemHealthQuery;
use Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList\GetIncidentListQuery;
use Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList\IncidentListDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Web\Module\Health\Route\StatusRoute;

#[AsController]
final class StatusController extends AbstractController
{
    private const int CACHE_TTL_SECONDS = 30;

    public function __construct(
        private readonly QueryBusComponentInterface $queryBus,
        private readonly CacheInterface $cache,
        private readonly StatusRoute $statusRoute,
    ) {
    }

    #[Route(
        path: StatusRoute::STATUS_PATH,
        name: StatusRoute::STATUS,
        methods: ['GET'],
    )]
    public function index(): Response
    {
        /** @var SystemHealthDto $systemHealth */
        $systemHealth = $this->cache->get(
            'status_page_system_health',
            function (ItemInterface $item): SystemHealthDto {
                $item->expiresAfter(self::CACHE_TTL_SECONDS);

                return $this->queryBus->query(new GetSystemHealthQuery());
            },
        );

        /** @var IncidentListDto $activeIncidents */
        $activeIncidents = $this->cache->get(
            'status_page_active_incidents',
            function (ItemInterface $item): IncidentListDto {
                $item->expiresAfter(self::CACHE_TTL_SECONDS);

                return $this->queryBus->query(new GetIncidentListQuery(
                    activeOnly: true,
                    pagination: new PaginationDto(limit: 10, offset: 0),
                    sort: new SortDto(['insTs' => SortDirectionEnum::desc]),
                ));
            },
        );

        $groupedServices = $this->groupServicesByCategory($systemHealth->services);

        return $this->render('@web.health/status/index.html.twig', [
            'systemHealth' => $systemHealth,
            'groupedServices' => $groupedServices,
            'statusRoute' => $this->statusRoute,
            'activeIncidents' => $activeIncidents->items,
        ]);
    }

    /**
     * @param ServiceHealthDto[] $services
     * @return array<string, ServiceHealthDto[]>
     */
    private function groupServicesByCategory(array $services): array
    {
        $grouped = [];

        foreach ($services as $service) {
            $categoryLabel = $this->getCategoryLabel($service->category);
            $grouped[$categoryLabel][] = $service;
        }

        return $grouped;
    }

    private function getCategoryLabel(ServiceCategoryEnum $category): string
    {
        return match ($category) {
            ServiceCategoryEnum::infrastructure => 'Infrastructure',
            ServiceCategoryEnum::llm => 'LLM Providers',
            ServiceCategoryEnum::externalApi => 'External APIs',
            ServiceCategoryEnum::cliTool => 'CLI Tools',
        };
    }
}
