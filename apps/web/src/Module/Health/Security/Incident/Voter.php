<?php

declare(strict_types=1);

namespace Web\Module\Health\Security\Incident;

use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter as SymfonyVoter;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

/**
 * @template TAttribute of string
 * @template TSubject of null
 * @extends SymfonyVoter<TAttribute, TSubject>
 */
final class Voter extends SymfonyVoter
{
    public function __construct(
        private readonly Rule $rule,
    ) {
    }

    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return ActionEnum::tryFrom($attribute) !== null;
    }

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof SymfonyUserInterface) {
            return false;
        }

        $action = ActionEnum::tryFrom($attribute);
        if ($action === null) {
            return false;
        }

        return match ($action) {
            ActionEnum::list => $this->rule->canList($token),
            ActionEnum::create => $this->rule->canCreate($token),
            ActionEnum::edit => $this->rule->canEdit($token),
            ActionEnum::resolve => $this->rule->canResolve($token),
            ActionEnum::delete => $this->rule->canDelete($token),
        };
    }
}
