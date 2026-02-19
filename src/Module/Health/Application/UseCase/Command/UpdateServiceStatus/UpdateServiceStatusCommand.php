<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\UpdateServiceStatus;

use Common\Application\Command\CommandInterface;
use Common\Module\Health\Domain\Enum\ServiceStatusEnum;

/**
 * @implements CommandInterface<void>
 */
final readonly class UpdateServiceStatusCommand implements CommandInterface
{
    public function __construct(
        public string $serviceName,
        public ServiceStatusEnum $status,
        public ?string $message = null,
        public ?float $responseTimeMs = null,
    ) {
    }
}
