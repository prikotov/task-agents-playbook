<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\UseCase\Command\RunHealthChecks;

use Common\Application\Command\CommandInterface;

/**
 * @implements CommandInterface<void>
 */
final readonly class RunHealthChecksCommand implements CommandInterface
{
    public function __construct(
        public ?string $category = null,
        public ?string $serviceName = null,
    ) {
    }
}
