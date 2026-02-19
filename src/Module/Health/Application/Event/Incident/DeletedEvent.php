<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Event\Incident;

use Common\Component\Event\Event;
use Symfony\Component\Uid\Uuid;

/**
 * Событие об удалении инцидента.
 */
final readonly class DeletedEvent extends Event
{
    public function __construct(
        private Uuid $incidentUuid,
        private string $title,
    ) {
        parent::__construct();
    }

    public function getIncidentUuid(): Uuid
    {
        return $this->incidentUuid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
