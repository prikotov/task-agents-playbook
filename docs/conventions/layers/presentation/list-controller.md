# Контроллер списка (List Controller)

## Определение

**Контроллер списка (List Controller)** — контроллер презентационного слоя для отображения страниц со списками,
фильтрацией, сортировкой и пагинацией. Он делегирует обработку данных форме фильтра и QueryBus, а рендеринг —
Twig-представлению. Основываемся на [Symfony Templating](https://symfony.com/doc/current/templates.html) и
Phoenix-компонентах.

## Общие правила

- Контроллер списка обслуживает только `GET`-запросы и объявляется `final`.
- Query-параметры преобразуем в DTO через `#[MapQueryString]` и валидируем компонентом Validator.
- Фильтры строим на FormType из слоя Presentation; состояние фильтра сериализуем в query string.
- Сортировка и пагинация выполняются через `PaginationRequestToApplicationDtoMapper` и `SortRequestToApplicationDtoMapper`.
- В шаблоне используем частичные представления `_search`, `_filter`, `_table` и Phoenix-компоненты.

## Зависимости

- Разрешено: `PaginationRequestDto`, `SortRequestDto`, соответствующие мапперы, FilterForm, Route, QueryBus.
- Запрещено: прямой доступ к репозиториям, SQL, сервисы Domain/Infrastructure.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Controller/<Context>/ListController.php
apps/<app>/src/Module/<ModuleName>/Form/<Context>/<Filter>FormType.php
apps/<app>/src/Module/<ModuleName>/Resource/templates/<context>/
```

## Как используем

1. `ListController` принимает `PaginationRequestDto`, `SortRequestDto`, `Request` и `#[CurrentUser]`.
2. Мапперы переводят DTO Presentation в Application DTO, применяем разрешённые поля сортировки.
3. Форма фильтра создаётся, обрабатывает запрос, модель формирует query параметры.
4. QueryBus выполняет запрос Application-UseCase и возвращает DTO со списком.
5. Шаблон рендерит частичные представления и Phoenix-компоненты, сохраняя сортировку/фильтры.

## Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\Billing\Controller\Plan;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Billing\Application\Enum\PlanStatusEnum;
use Common\Module\Billing\Application\UseCase\Query\Plan\Find\FindQuery;
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
use Web\Module\Billing\Form\Plan\FilterFormModel;
use Web\Module\Billing\Form\Plan\FilterFormType;
use Web\Module\Billing\Route\PlanRoute;
use Web\Security\UserInterface;

#[Route(path: PlanRoute::LIST_PATH, name: PlanRoute::LIST, methods: [Request::METHOD_GET])]
#[AsController]
final class ListController extends AbstractController
{
    public function __construct(
        private readonly PaginationRequestToApplicationDtoMapper $paginationRequestToApplicationDtoMapper,
        private readonly SortRequestToApplicationDtoMapper $sortRequestToApplicationDtoMapper,
        private readonly QueryBusComponentInterface $queryBus,
        private readonly PlanRoute $planRoute,
    ) {
    }

    public function __invoke(
        #[CurrentUser] UserInterface $currentUser,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        PaginationRequestDto $paginationRequestDto,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        SortRequestDto $sortRequestDto,
        Request $request,
    ): Response {
        $pagination = $this->paginationRequestToApplicationDtoMapper->map($paginationRequestDto);
        $sort = $this->sortRequestToApplicationDtoMapper->map(
            sortRequest: $sortRequestDto,
            defaultSort: '-createdAt',
            allowedSorts: ['name', 'createdAt', 'status', 'currency'],
        );

        $filterForm = $this->createForm(FilterFormType::class, new FilterFormModel());
        $filterForm->handleRequest($request);
        /** @var FilterFormModel $filter */
        $filter = $filterForm->getData();

        $status = $filter->getStatus();
        $plans = $this->queryBus->query(new FindQuery(
            search: $filter->getSearch(),
            status: $status === null ? null : PlanStatusEnum::from($status->value),
            onlyActive: $currentUser->isNotAdmin(),
            pagination: $pagination,
            sort: $sort,
        ));

        return $this->render('@web.billing/plan/list.html.twig', [
            'plans' => $plans->items,
            'total' => $plans->total,
            'planRoute' => $this->planRoute,
            'pagination' => $paginationRequestDto,
            'sort' => $sortRequestDto,
            'filterForm' => $filterForm,
            'filter' => $filter->toQueryParams($filterForm->getName()),
        ]);
    }
}
```

## Чек-лист для проведения ревью кода

- [ ] Контроллер обслуживает только `GET`, объявлен `final` и лежит в каталоге Presentation.
- [ ] Используются `PaginationRequestDto`/`SortRequestDto` и их мапперы с whitelisting полей.
- [ ] Форма фильтра объявлена в слое Presentation и сериализует состояние в query string.
- [ ] QueryBus/UseCase получают строго необходимые параметры.
- [ ] Шаблон списка использует Phoenix-компоненты и частичные блоки `_search`, `_filter`, `_table`.
