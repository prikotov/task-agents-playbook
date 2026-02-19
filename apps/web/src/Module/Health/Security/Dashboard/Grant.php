<?php

declare(strict_types=1);

namespace Web\Module\Health\Security\Dashboard;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final readonly class Grant
{
    public function __construct(private AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function canView(): bool
    {
        return $this->authorizationChecker->isGranted(ActionEnum::view->value);
    }
}
