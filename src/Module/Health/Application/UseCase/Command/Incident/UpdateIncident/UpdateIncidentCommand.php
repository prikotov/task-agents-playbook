<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\Incident\UpdateIncident;

use Common\Application\Command\CommandInterface;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum;
use Common\Module\Health\Domain\Enum\IncidentStatusEnum;
use Symfony\Component\Uid\Uuid;

/**
 * @implements CommandInterface<void>
 */
final readonly class UpdateIncidentCommand implements CommandInterface
{
    /**
     * @param array<int, string>|null $affectedServiceNames null = не обновлять
     */
    public function __construct(
        public Uuid $uuid,
        public ?string $title = null,
        public ?string $description = null,
        public ?IncidentStatusEnum $status = null,
        public ?IncidentSeverityEnum $severity = null,
        public ?array $affectedServiceNames = null,
    ) {
    }
}
