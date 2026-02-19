<?php

declare(strict_types=1);

namespace Web\Module\Health\Route;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

final readonly class IncidentRoute
{
    public const LIST = 'admin_incidents';
    public const LIST_PATH = '/admin/incidents';

    public const NEW = 'admin_incident_new';
    public const NEW_PATH = '/admin/incidents/new';

    public const EDIT = 'admin_incident_edit';
    public const EDIT_PATH = '/admin/incidents/{uuid}/edit';

    public const RESOLVE = 'admin_incident_resolve';
    public const RESOLVE_PATH = '/admin/incidents/{uuid}/resolve';

    public const DELETE = 'admin_incident_delete';
    public const DELETE_PATH = '/admin/incidents/{uuid}/delete';

    public function __construct(private RouterInterface $router)
    {
    }

    public function list(): string
    {
        return $this->router->generate(self::LIST);
    }

    public function new(): string
    {
        return $this->router->generate(self::NEW);
    }

    public function edit(Uuid $uuid): string
    {
        return $this->router->generate(self::EDIT, ['uuid' => $uuid->toRfc4122()]);
    }

    public function resolve(Uuid $uuid): string
    {
        return $this->router->generate(self::RESOLVE, ['uuid' => $uuid->toRfc4122()]);
    }

    public function delete(Uuid $uuid): string
    {
        return $this->router->generate(self::DELETE, ['uuid' => $uuid->toRfc4122()]);
    }
}
