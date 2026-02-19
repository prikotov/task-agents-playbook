<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Query\Incident\GetIncident;

use Common\Application\Query\QueryInterface;
use Common\Module\Health\Application\Dto\IncidentDto;
use Symfony\Component\Uid\Uuid;

/**
 * @implements QueryInterface<IncidentDto>
 */
final readonly class GetIncidentQuery implements QueryInterface
{
    public function __construct(
        public Uuid $uuid,
    ) {
    }
}
