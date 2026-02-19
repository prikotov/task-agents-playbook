<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\Incident\DeleteIncident;

use Common\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @implements CommandInterface<void>
 */
final readonly class DeleteIncidentCommand implements CommandInterface
{
    public function __construct(
        public Uuid $uuid,
    ) {
    }
}
