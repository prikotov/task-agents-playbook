<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Query\GetServiceStatus;

use Common\Application\Query\QueryInterface;
use Common\Module\Health\Application\Dto\ServiceHealthDto;

/**
 * @implements QueryInterface<ServiceHealthDto>
 */
final readonly class GetServiceStatusQuery implements QueryInterface
{
    public function __construct(
        public string $serviceName,
    ) {
    }
}
