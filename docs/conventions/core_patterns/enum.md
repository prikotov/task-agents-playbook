# Перечисление (Enum)

**Перечисление (Enum)** — нативное средство PHP, предназначенное для представления конечного набора вариантов
значения (начиная с [PHP 8.1](https://www.php.net/manual/en/language.enumerations.php)).
Используются вместо набора констант. Чаще всего применяются для описания статусов, состояний или фиксированных атрибутов
сущностей и процессов.

## Общие правила

* Разрешены в любом слое (Domain, Application, Infrastructure, Integration, Presentation).
* ApplicationEnum — перечисления уровня Application, используемые в публичных контрактах (DTO/UseCase), чтобы не "подтягивать" доменные enum в Presentation/Integration. Для соответствия доменной модели используйте явные мапперы между `Domain\Enum` и `Application\Enum`.
* ❗ **Enum не содержит бизнес-логики**, зависимостей, магических методов и дополнительных констант.
* Используются [backed enum](https://www.php.net/manual/en/language.enumerations.backed.php) (int|string) при
  необходимости хранить/отображать читаемое значение.
* Названия самих `enum` — в PascalCase с постфиксом `Enum`.
* Названия `case` — в `camelCase`.
* Если enum универсален и не связан с конкретным модулем (например: `LanguageEnum`, `GenderEnum`), его размещают в
  `Common\Enum`.

## Расположение

* Domain
  - `Common\Module\{ModuleName}\Domain\Enum\{Name}Enum`
* Application
  - `Common\Module\{ModuleName}\Application\Enum\{Name}Enum` — для общих enum’ов уровня Application
  - `Common\Module\{ModuleName}\Application\UseCase\{Name}\{Name}Enum` — для enum’ов, связанных строго с одним UseCase.
* Infrastructure
  - `Common\Module\{ModuleName}\Infrastructure\Enum\{Name}Enum`
  - `Common\Module\{ModuleName}\Infrastructure\Component\{SomeComponent}\Enum\{Name}Enum`
* Integration
  - `Common\Module\{ModuleName}\Integration\Enum\{Name}Enum`
  - `Common\Module\{ModuleName}\Integration\Component\{SomeComponent}\Enum\{Name}Enum`
* Presentation
  - `Apps\Web\Module\{ModuleName}\Enum\{Name}Enum`
* Common
  - `Common\Enum\{Name}Enum`

## Как используем

* ❗ Enum доступен только в своём модуле.
* Если требуется значение из чужого модуля → создаём собственный enum (копию) и маппим через интеграционный сервис.
* Используем напрямую в сравнении или `match`, без вызова `->value`, когда это возможно.
* При попытке создать enum из невалидного значения выбрасывается `ValueError`, оборачиваем его в `ValidationException`.

## Примеры

Пример создания Enum:

```php
<?php

declare(strict_types=1);

namespace Common\Module\Chat\Domain\Enum;

enum ChatMessageRoleEnum: int
{
    case system = 1;
    case user = 2;
    case assistant = 3;
    case tool = 4;
}
```

Пример использования Enum в match:

```php
<?php

declare(strict_types=1);

namespace Web\Component\Twig\Project;

use Common\Module\Project\Application\Enum\ProjectStatusEnum;
use InvalidArgumentException;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Web\Component\Twig\Phoenix\Badge;

#[AsTwigComponent(template: 'components/Phoenix/Badge.html.twig')]
final class StatusBadge extends Badge
{
    public function mount(ProjectStatusEnum $status): void
    {
        $type = match ($status) {
            ProjectStatusEnum::new => Badge::PRIMARY,
            ProjectStatusEnum::closed => Badge::SECONDARY,
            ProjectStatusEnum::active => Badge::SUCCESS,
            ProjectStatusEnum::deleted => Badge::DANGER,
            default => throw new InvalidArgumentException(sprintf(
                'Unknown project status: "%s".',
                $status->value,
            )),
        };

        $this->type = $type;
        $this->text = $status->value;
    }
}
```

Пример обработки исключения \ValueError:

```php
use Common\Exception\ValidationException;

try {
    $enum = ProjectStatusEnum::from($input);
} catch (\ValueError) {
    throw new ValidationException('Unknown value.');
}
```
