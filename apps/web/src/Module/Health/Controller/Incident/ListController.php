<?php

declare(strict_types=1);

namespace Web\Module\Health\Controller\Incident;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList\GetIncidentListQuery;
use Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList\IncidentListDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Web\Component\Pagination\PaginationRequestDto;
use Web\Component\Pagination\PaginationRequestToApplicationDtoMapper;
use Web\Component\Sort\SortRequestDto;
use Web\Component\Sort\SortRequestToApplicationDtoMapper;
use Web\Module\Health\Form\Incident\FilterFormModel;
use Web\Module\Health\Form\Incident\FilterFormType;
use Web\Module\Health\Route\IncidentRoute;
use Web\Module\Health\Security\Incident\Grant;
use Web\Security\UserInterface;

#[Route(
    path: IncidentRoute::LIST_PATH,
    name: IncidentRoute::LIST,
    methods: [Request::METHOD_GET],
)]
#[AsController]
final class ListController extends AbstractController
{
    public function __construct(
        private readonly QueryBusComponentInterface $queryBus,
        private readonly IncidentRoute $incidentRoute,
        private readonly Grant $grant,
        private readonly PaginationRequestToApplicationDtoMapper $paginationRequestToApplicationDtoMapper,
        private readonly SortRequestToApplicationDtoMapper $sortRequestToApplicationDtoMapper,
    ) {
    }

    public function __invoke(
        #[CurrentUser]
        UserInterface $currentUser,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        PaginationRequestDto $paginationRequestDto,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        SortRequestDto $sortRequestDto,
        Request $request,
    ): Response {
        if (!$this->grant->canList()) {
            throw $this->createAccessDeniedException();
        }

        $pagination = $this->paginationRequestToApplicationDtoMapper->map($paginationRequestDto);
        $sort = $this->sortRequestToApplicationDtoMapper->map(
            sortRequest: $sortRequestDto,
            defaultSort: '-insTs',
            allowedSorts: ['insTs', 'title', 'status', 'severity'],
        );

        $filterForm = $this->createForm(FilterFormType::class, new FilterFormModel());
        $filterForm->handleRequest($request);
        /** @var FilterFormModel $filter */
        $filter = $filterForm->getData();

        /** @var IncidentListDto $result */
        $result = $this->queryBus->query(new GetIncidentListQuery(
            status: $filter->getStatus(),
            severity: $filter->getSeverity(),
            activeOnly: $filter->isActiveOnly(),
            pagination: $pagination,
            sort: $sort,
        ));

        $hasMore = ($result->page * $result->perPage) < $result->total;

        return $this->render('@web.health/incident/index.html.twig', [
            'incidents' => $result->items,
            'total' => $result->total,
            'page' => $result->page,
            'perPage' => $result->perPage,
            'hasMore' => $hasMore,
            'incidentRoute' => $this->incidentRoute,
            'pagination' => $paginationRequestDto,
            'sort' => $sortRequestDto,
            'filterForm' => $filterForm,
            'filter' => $filter->toQueryParams($filterForm->getName()),
            'grant' => $this->grant,
        ]);
    }
}
