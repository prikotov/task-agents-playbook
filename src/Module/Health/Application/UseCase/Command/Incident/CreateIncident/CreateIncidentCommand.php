<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\Incident\CreateIncident;

use Common\Application\Command\CommandInterface;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum;
use Symfony\Component\Uid\Uuid;

/**
 * @implements CommandInterface<Uuid>
 */
final readonly class CreateIncidentCommand implements CommandInterface
{
    /**
     * @param array<int, string> $affectedServiceNames
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public IncidentSeverityEnum $severity = IncidentSeverityEnum::minor,
        public array $affectedServiceNames = [],
    ) {
    }
}
