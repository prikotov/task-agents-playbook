# Голосующий объект (Voter)

## Определение

**Голосующий объект (Voter)** — класс, реализующий интерфейс авторизации Symfony и принимающий решение о
доступе на основании Permission Enum и Rule. Подробности — в официальной документации
[Security Voters](https://symfony.com/doc/current/security/voters.html).

## Общие правила

- Класс наследуется от `Symfony\Component\Security\Core\Authorization\Voter\Voter`.
- Используем PHPDoc `@template-extends` с атрибутом/субъектом для статики.
- Внедряем Rule через конструктор, никакой логики напрямую в Voter.
- Метод `supports()` проверяет и атрибут, и валидность subject.
- Метод `voteOnAttribute()` преобразует атрибут в enum действия и делегирует Rule.

## Зависимости

- Разрешено: Rule, Permission Enum, `TokenInterface`, DTO/Value Object Presentation.
- Запрещено: прямой доступ к контейнеру, сервисам Domain/Infrastructure.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Security/<SubjectName>/Voter.php
```

## Как используем

1. Регистрируем Voter как сервис (автоконфигурация делает это автоматически).
2. Контроллеры вызывают `$this->isGranted(ActionEnum::case->value, $subject)`.
3. Voter преобразует строковый атрибут в `ActionEnum` и делегирует проверку Rule.
4. Subject должен быть простой структурой (`array`, `Uuid`, DTO), понятной Voter.

## Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\Controller\Project\Security;

use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Web\Module\Project\Controller\Project\Enum\ProjectActionEnum;

/**
 * @template TAttribute of string
 * @template TSubject of array<string, ?Uuid>
 * @extends Voter<TAttribute, TSubject>
 */
final class ProjectVoter extends Voter
{
    public function __construct(
        private readonly ProjectRule $projectRule,
    ) {
    }

    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return ProjectActionEnum::tryFrom($attribute) !== null
            && $this->isSubjectValid($subject);
    }

    private function isSubjectValid(mixed $subject): bool
    {
        if (
            array_key_exists('projectUuid', $subject)
            && ($subject['projectUuid'] === null || $subject['projectUuid'] instanceof Uuid)
        ) {
            return true;
        }

        if (
            array_key_exists('userUuid', $subject)
            && ($subject['userUuid'] === null || $subject['userUuid'] instanceof Uuid)
        ) {
            return true;
        }

        return false;
    }

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        $action = ProjectActionEnum::tryFrom($attribute);
        if ($action === null) {
            return false;
        }

        return match ($action) {
            ProjectActionEnum::create => $this->projectRule->canCreate(
                token: $token,
                userUuid: $subject['userUuid'],
            ),
            ProjectActionEnum::view => $this->projectRule->canView(
                token: $token,
                userUuid: $subject['userUuid'] ?? null,
                projectUuid: $subject['projectUuid'] ?? null,
            ),
            ProjectActionEnum::edit => $this->projectRule->canEdit(
                token: $token,
                projectUuid: $subject['projectUuid'],
            ),
            ProjectActionEnum::delete => $this->projectRule->canDelete(
                token: $token,
                projectUuid: $subject['projectUuid'],
            ),
        };
    }
}
```

## Чек-лист для проведения ревью кода

- [ ] Voter лежит в каталоге Security и объявлен `final`.
- [ ] Метод `supports()` валидирует и атрибут, и subject.
- [ ] В `voteOnAttribute()` нет сложной логики — все делегируется Rule.
- [ ] Используем Action Enum/Permission Enum вместо строк.
- [ ] Subject описан и типизирован (через phpdoc) так, чтобы избежать ошибок времени выполнения.
