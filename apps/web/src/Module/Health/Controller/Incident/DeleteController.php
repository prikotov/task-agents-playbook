<?php

declare(strict_types=1);

namespace Web\Module\Health\Controller\Incident;

use Common\Application\Component\CommandBus\CommandBusComponentInterface;
use Common\Module\Health\Application\UseCase\Command\Incident\DeleteIncident\DeleteIncidentCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use Web\Module\Health\Route\IncidentRoute;
use Web\Module\Health\Security\Incident\Grant;
use Web\Security\UserInterface;

#[Route(
    path: IncidentRoute::DELETE_PATH,
    name: IncidentRoute::DELETE,
    methods: [Request::METHOD_POST],
)]
#[AsController]
final class DeleteController extends AbstractController
{
    public function __construct(
        private readonly CommandBusComponentInterface $commandBus,
        private readonly Grant $grant,
    ) {
    }

    public function __invoke(
        string $uuid,
        #[CurrentUser]
        UserInterface $currentUser,
    ): RedirectResponse {
        if (!$this->grant->canDelete()) {
            throw $this->createAccessDeniedException();
        }

        $this->commandBus->execute(new DeleteIncidentCommand(
            uuid: Uuid::fromString($uuid),
        ));

        $this->addFlash('success', 'Incident deleted successfully.');

        return $this->redirectToRoute(IncidentRoute::LIST);
    }
}
