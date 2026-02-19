<?php

declare(strict_types=1);

namespace Web\Module\Health\Controller\Incident;

use Common\Application\Component\CommandBus\CommandBusComponentInterface;
use Common\Module\Health\Application\UseCase\Command\Incident\CreateIncident\CreateIncidentCommand;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum as DomainIncidentSeverityEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Web\Module\Health\Form\Incident\CreateFormModel;
use Web\Module\Health\Form\Incident\CreateFormType;
use Web\Module\Health\Route\IncidentRoute;
use Web\Module\Health\Security\Incident\Grant;
use Web\Security\UserInterface;

#[Route(
    path: IncidentRoute::NEW_PATH,
    name: IncidentRoute::NEW,
    methods: [Request::METHOD_GET, Request::METHOD_POST],
)]
#[AsController]
final class CreateController extends AbstractController
{
    public function __construct(
        private readonly CommandBusComponentInterface $commandBus,
        private readonly IncidentRoute $incidentRoute,
        private readonly Grant $grant,
    ) {
    }

    public function __invoke(
        Request $request,
        #[CurrentUser]
        UserInterface $currentUser,
    ): Response {
        if (!$this->grant->canCreate()) {
            throw $this->createAccessDeniedException();
        }

        $formModel = new CreateFormModel();
        $form = $this->createForm(CreateFormType::class, $formModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $affectedServiceNames = array_filter(
                array_map('trim', $request->request->all('affectedServiceNames')),
            );

            $this->commandBus->execute(new CreateIncidentCommand(
                title: $formModel->getTitle(),
                description: $formModel->getDescription(),
                severity: DomainIncidentSeverityEnum::from($formModel->getSeverity()->value),
                affectedServiceNames: $affectedServiceNames,
            ));

            $this->addFlash('success', 'Incident created successfully.');

            return $this->redirectToRoute(IncidentRoute::LIST);
        }

        return $this->render('@web.health/incident/new.html.twig', [
            'form' => $form,
            'incidentRoute' => $this->incidentRoute,
        ]);
    }
}
