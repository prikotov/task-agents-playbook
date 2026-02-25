# Маршрут (Route)

**Маршрут (Route)** — вспомогательные объект, инкапсулирующий имена и пути маршрутов, а также генерацию URL в слое
представления. Как работает маршрутизация в Symfony смотри [документацию](https://symfony.com/doc/current/routing.html).

## Общие правила

- Класс объявляется `final`.
- Каждый маршрут задаётся парой констант:
  - `NAME` — имя маршрута.
  - `NAME_PATH` — путь маршрута.
- В конструктор внедряется `RouterInterface`.
- Публичные методы формируют URL и именуются в `lowerCamelCase` по имени константы.
- Аргументы методов строго типизированы.

## Зависимости

- Разрешено: `RouterInterface`.
- Запрещено: зависимости из других модулей и слоёв.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Route/<Entity>Route.php
```

## Как используем

- Константы применяются в атрибутах `#[Route]` контроллеров.
- Методы вызываются в шаблонах или сервисах для генерации ссылок.

Пример в Twig:

```twig
{# допустимый вызов через path() с константой #}
<a href="{{ path(constant('Web\\Module\\User\\Route\\InvitationRoute::LIST')) }}">...</a>

{# предпочтительно — через Route-класс #}
<a href="{{ invitationRoute.list() }}">...</a>
```

## Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\User\Route;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

final readonly class InvitationRoute
{
    public const LIST = 'invitations';
    public const LIST_PATH = '/invitations';

    public const RESEND = 'invitation_resend';
    public const RESEND_PATH = '/user/invitations/{uuid}/resend';

    public function __construct(private RouterInterface $router)
    {
    }

    public function list(): string
    {
        return $this->router->generate(self::LIST);
    }

    public function resend(Uuid $uuid): string
    {
        return $this->router->generate(self::RESEND, ['uuid' => $uuid->toRfc4122()]);
    }
}
```

## Чек-лист для проведения ревью кода

- [ ] Класс объявлен `final` и хранит только константы и методы генерации URL.
- [ ] Константы названы по схеме `NAME` и `NAME_PATH`.
- [ ] Внедрён только `RouterInterface`, нет зависимостей из других модулей.
- [ ] Методы типизированы и используют `RouterInterface::generate()`.
