<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\Incident\ResolveIncident;

use Common\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @implements CommandInterface<void>
 */
final readonly class ResolveIncidentCommand implements CommandInterface
{
    public function __construct(
        public Uuid $uuid,
    ) {
    }
}
