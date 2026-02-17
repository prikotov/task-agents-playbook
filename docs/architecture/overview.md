# Архитектурные подходы и опорные практики

Документ фиксирует базовые архитектурные решения проекта и точки опоры на Symfony.
Для деталей по конкретным подсистемам используем специализированные документы в `docs/architecture/*`.

## Контекст проекта

- Symfony 7.3 + PHP 8.4.
- DDD и модульная структура: `src/Module/<ModuleName>` + `apps/<app>` для Web/API/Console.
- Слои: Domain, Application, Infrastructure, Integration, Presentation.
- Асинхронные пайплайны и фоновые задачи — Symfony Messenger.
- UI: Symfony UX (Turbo, Stimulus), AssetMapper, Twig, Bootstrap 5 Phoenix.

## Ключевые архитектурные правила

- Presentation обращается только к Application (контроллеры и команды не трогают Domain напрямую).
- Межмодульные интеграции идут через Integration-слой.
- Долгие/ресурсоёмкие операции выносятся в очереди Messenger.
- Live-обновления UI строятся через Mercure + Turbo Streams.

## Официальные ориентиры Symfony

- Controllers: https://symfony.com/doc/current/controller.html
- Routing: https://symfony.com/doc/current/routing.html
- Twig Templates: https://symfony.com/doc/current/templates.html
- Security & Authorization: https://symfony.com/doc/current/security.html
- Forms: https://symfony.com/doc/current/forms.html
- Messenger: https://symfony.com/doc/current/messenger.html
- Mercure: https://symfony.com/doc/current/mercure.html
- UX Turbo: https://symfony.com/doc/current/ux/turbo.html
- AssetMapper: https://symfony.com/doc/current/frontend/asset_mapper.html

## Связанные документы

- Source Processing (AS-IS): `docs/architecture/source-processing/00-index.md`
- Notification design: `docs/architecture/module/notification-design.md`
- Production infrastructure: `docs/architecture/production-infrastructure.md`
