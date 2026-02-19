<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Application\UseCase\Command\RunHealthChecks;

use Common\Application\Command\CommandInterface;
use Common\Module\Health\Application\UseCase\Command\RunHealthChecks\RunHealthChecksCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Application\UseCase\Command\RunHealthChecks\RunHealthChecksCommand
 */
final class RunHealthChecksCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $command = new RunHealthChecksCommand();

        self::assertInstanceOf(CommandInterface::class, $command);
    }

    public function testDefaultValues(): void
    {
        $command = new RunHealthChecksCommand();

        self::assertNull($command->category);
        self::assertNull($command->serviceName);
    }

    public function testConstructorSetsCategory(): void
    {
        $command = new RunHealthChecksCommand(category: 'infrastructure');

        self::assertSame('infrastructure', $command->category);
        self::assertNull($command->serviceName);
    }

    public function testConstructorSetsServiceName(): void
    {
        $command = new RunHealthChecksCommand(serviceName: 'postgresql');

        self::assertNull($command->category);
        self::assertSame('postgresql', $command->serviceName);
    }

    public function testConstructorSetsAllParameters(): void
    {
        $command = new RunHealthChecksCommand(category: 'llm', serviceName: 'openai');

        self::assertSame('llm', $command->category);
        self::assertSame('openai', $command->serviceName);
    }
}
