<?php

declare(strict_types=1);

namespace Web\Module\Health\Security\Incident;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final readonly class Grant
{
    public function __construct(private AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function canList(): bool
    {
        return $this->authorizationChecker->isGranted(ActionEnum::list->value);
    }

    public function canCreate(): bool
    {
        return $this->authorizationChecker->isGranted(ActionEnum::create->value);
    }

    public function canEdit(): bool
    {
        return $this->authorizationChecker->isGranted(ActionEnum::edit->value);
    }

    public function canResolve(): bool
    {
        return $this->authorizationChecker->isGranted(ActionEnum::resolve->value);
    }

    public function canDelete(): bool
    {
        return $this->authorizationChecker->isGranted(ActionEnum::delete->value);
    }
}
