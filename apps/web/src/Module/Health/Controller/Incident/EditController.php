<?php

declare(strict_types=1);

namespace Web\Module\Health\Controller\Incident;

use Common\Application\Component\CommandBus\CommandBusComponentInterface;
use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Health\Application\Dto\IncidentDto;
use Common\Module\Health\Application\UseCase\Command\Incident\UpdateIncident\UpdateIncidentCommand;
use Common\Module\Health\Application\UseCase\Query\Incident\GetIncident\GetIncidentQuery;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum as DomainIncidentSeverityEnum;
use Common\Module\Health\Domain\Enum\IncidentStatusEnum as DomainIncidentStatusEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use Web\Module\Health\Form\Incident\EditFormModel;
use Web\Module\Health\Form\Incident\EditFormType;
use Web\Module\Health\Route\IncidentRoute;
use Web\Module\Health\Security\Incident\Grant;
use Web\Security\UserInterface;

#[Route(
    path: IncidentRoute::EDIT_PATH,
    name: IncidentRoute::EDIT,
    methods: [Request::METHOD_GET, Request::METHOD_POST],
)]
#[AsController]
final class EditController extends AbstractController
{
    public function __construct(
        private readonly QueryBusComponentInterface $queryBus,
        private readonly CommandBusComponentInterface $commandBus,
        private readonly IncidentRoute $incidentRoute,
        private readonly Grant $grant,
    ) {
    }

    public function __invoke(
        string $uuid,
        Request $request,
        #[CurrentUser]
        UserInterface $currentUser,
    ): Response {
        if (!$this->grant->canEdit()) {
            throw $this->createAccessDeniedException();
        }

        $incidentUuid = Uuid::fromString($uuid);
        /** @var IncidentDto $incident */
        $incident = $this->queryBus->query(new GetIncidentQuery(uuid: $incidentUuid));

        $formModel = self::createFormModelFromIncident($incident);
        $form = $this->createForm(EditFormType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $affectedServiceNames = array_filter(
                array_map('trim', $request->request->all('affectedServiceNames')),
            );

            $this->commandBus->execute(new UpdateIncidentCommand(
                uuid: $incidentUuid,
                title: $formModel->getTitle(),
                description: $formModel->getDescription(),
                status: DomainIncidentStatusEnum::from($formModel->getStatus()->value),
                severity: DomainIncidentSeverityEnum::from($formModel->getSeverity()->value),
                affectedServiceNames: $affectedServiceNames,
            ));

            $this->addFlash('success', 'Incident updated successfully.');

            return $this->redirectToRoute(IncidentRoute::LIST);
        }

        return $this->render('@web.health/incident/edit.html.twig', [
            'form' => $form,
            'incident' => $incident,
            'incidentRoute' => $this->incidentRoute,
        ]);
    }

    private static function createFormModelFromIncident(IncidentDto $incident): EditFormModel
    {
        $model = new EditFormModel();
        $model->setTitle($incident->title);
        $model->setDescription($incident->description);
        $model->setStatus($incident->status);
        $model->setSeverity($incident->severity);

        return $model;
    }
}
