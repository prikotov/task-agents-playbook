<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Event\Incident;

use Common\Component\Event\Event;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum;
use Symfony\Component\Uid\Uuid;

/**
 * Событие о создании инцидента.
 */
final readonly class CreatedEvent extends Event
{
    public function __construct(
        private Uuid $incidentUuid,
        private string $title,
        private IncidentSeverityEnum $severity,
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

    public function getSeverity(): IncidentSeverityEnum
    {
        return $this->severity;
    }
}
