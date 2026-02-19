<?php

declare(strict_types=1);

namespace Web\Module\Health\Controller\Admin;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Health\Application\Dto\ServiceHealthDto;
use Common\Module\Health\Application\Dto\SystemHealthDto;
use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use Common\Module\Health\Application\Enum\ServiceStatusEnum;
use Common\Module\Health\Application\UseCase\Query\GetSystemHealth\GetSystemHealthQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Web\Module\Health\Form\Dashboard\FilterFormModel;
use Web\Module\Health\Form\Dashboard\FilterFormType;
use Web\Module\Health\Map\ServiceCategoryLabelMap;
use Web\Module\Health\Route\DashboardRoute;
use Web\Module\Health\Security\Dashboard\Grant;
use Web\Security\UserInterface;

#[AsController]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly QueryBusComponentInterface $queryBus,
        private readonly Grant $grant,
        private readonly DashboardRoute $dashboardRoute,
        private readonly ServiceCategoryLabelMap $categoryLabelMap,
    ) {
    }

    #[Route(
        path: DashboardRoute::DASHBOARD_PATH,
        name: DashboardRoute::DASHBOARD,
        methods: [Request::METHOD_GET],
    )]
    public function index(
        #[CurrentUser]
        UserInterface $currentUser,
        Request $request,
    ): Response {
        if (!$this->grant->canView()) {
            throw $this->createAccessDeniedException();
        }

        /** @var SystemHealthDto $systemHealth */
        $systemHealth = $this->queryBus->query(new GetSystemHealthQuery());

        $filterForm = $this->createForm(FilterFormType::class, new FilterFormModel());
        $filterForm->handleRequest($request);
        /** @var FilterFormModel $filter */
        $filter = $filterForm->getData();

        $filteredServices = $this->filterServices(
            $systemHealth->services,
            $filter->getCategory(),
            $filter->getStatus(),
        );

        $groupedServices = $this->groupServicesByCategory($filteredServices);

        return $this->render('@web.health/admin/dashboard/index.html.twig', [
            'systemHealth' => $systemHealth,
            'groupedServices' => $groupedServices,
            'dashboardRoute' => $this->dashboardRoute,
            'filterForm' => $filterForm,
            'filter' => $filter->toQueryParams($filterForm->getName()),
        ]);
    }

    /**
     * @param ServiceHealthDto[] $services
     * @return ServiceHealthDto[]
     */
    private function filterServices(
        array $services,
        ?ServiceCategoryEnum $category,
        ?ServiceStatusEnum $status,
    ): array {
        return array_values(array_filter(
            $services,
            static fn (ServiceHealthDto $service): bool =>
                ($category === null || $service->category === $category)
                && ($status === null || $service->status === $status),
        ));
    }

    /**
     * @param ServiceHealthDto[] $services
     * @return array<string, ServiceHealthDto[]>
     */
    private function groupServicesByCategory(array $services): array
    {
        $grouped = [];

        foreach ($services as $service) {
            $categoryLabel = $this->categoryLabelMap->getAssociationByValue($service->category);
            $grouped[$categoryLabel][] = $service;
        }

        return $grouped;
    }
}
