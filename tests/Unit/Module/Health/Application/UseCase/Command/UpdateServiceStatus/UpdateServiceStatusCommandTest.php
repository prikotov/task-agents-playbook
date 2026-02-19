<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Application\UseCase\Command\UpdateServiceStatus;

use Common\Application\Command\CommandInterface;
use Common\Module\Health\Application\UseCase\Command\UpdateServiceStatus\UpdateServiceStatusCommand;
use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Application\UseCase\Command\UpdateServiceStatus\UpdateServiceStatusCommand
 */
final class UpdateServiceStatusCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $command = new UpdateServiceStatusCommand(
            serviceName: 'postgresql',
            status: ServiceStatusEnum::operational,
        );

        self::assertInstanceOf(CommandInterface::class, $command);
    }

    public function testConstructorSetsRequiredProperties(): void
    {
        $command = new UpdateServiceStatusCommand(
            serviceName: 'postgresql',
            status: ServiceStatusEnum::outage,
        );

        self::assertSame('postgresql', $command->serviceName);
        self::assertSame(ServiceStatusEnum::outage, $command->status);
    }

    public function testDefaultOptionalPropertiesAreNull(): void
    {
        $command = new UpdateServiceStatusCommand(
            serviceName: 'postgresql',
            status: ServiceStatusEnum::operational,
        );

        self::assertNull($command->message);
        self::assertNull($command->responseTimeMs);
    }

    public function testConstructorSetsOptionalProperties(): void
    {
        $command = new UpdateServiceStatusCommand(
            serviceName: 'postgresql',
            status: ServiceStatusEnum::degraded,
            message: 'Slow response time',
            responseTimeMs: 500.5,
        );

        self::assertSame('Slow response time', $command->message);
        self::assertSame(500.5, $command->responseTimeMs);
    }
}
