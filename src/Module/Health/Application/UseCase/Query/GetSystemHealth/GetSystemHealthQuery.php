<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Query\GetSystemHealth;

use Common\Application\Query\QueryInterface;
use Common\Module\Health\Application\Dto\SystemHealthDto;

/**
 * @implements QueryInterface<SystemHealthDto>
 */
final readonly class GetSystemHealthQuery implements QueryInterface
{
    public function __construct(
        public ?string $category = null,
    ) {
    }
}
