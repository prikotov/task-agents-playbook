<?php

declare(strict_types=1);

namespace Web\Module\Health\Controller\Incident;

use Common\Application\Component\CommandBus\CommandBusComponentInterface;
use Common\Module\Health\Application\UseCase\Command\Incident\ResolveIncident\ResolveIncidentCommand;
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
    path: IncidentRoute::RESOLVE_PATH,
    name: IncidentRoute::RESOLVE,
    methods: [Request::METHOD_POST],
)]
#[AsController]
final class ResolveController extends AbstractController
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
        if (!$this->grant->canResolve()) {
            throw $this->createAccessDeniedException();
        }

        $this->commandBus->execute(new ResolveIncidentCommand(
            uuid: Uuid::fromString($uuid),
        ));

        $this->addFlash('success', 'Incident resolved successfully.');

        return $this->redirectToRoute(IncidentRoute::LIST);
    }
}
