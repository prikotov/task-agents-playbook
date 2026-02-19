<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList;

use Common\Module\Health\Application\Dto\IncidentDto;

/**
 * DTO для списка инцидентов с пагинацией.
 */
final readonly class IncidentListDto
{
    /**
     * @param array<int, IncidentDto> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }
}
