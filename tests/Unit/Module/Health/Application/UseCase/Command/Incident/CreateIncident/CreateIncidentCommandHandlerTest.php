<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Application\UseCase\Command\Incident\CreateIncident;

use Common\Component\Event\EventBusInterface;
use Common\Component\Persistence\PersistenceManagerInterface;
use Common\Module\Health\Application\Event\Incident\CreatedEvent;
use Common\Module\Health\Application\UseCase\Command\Incident\CreateIncident\CreateIncidentCommand;
use Common\Module\Health\Application\UseCase\Command\Incident\CreateIncident\CreateIncidentCommandHandler;
use Common\Module\Health\Domain\Entity\IncidentModel;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * @covers \Common\Module\Health\Application\UseCase\Command\Incident\CreateIncident\CreateIncidentCommandHandler
 */
final class CreateIncidentCommandHandlerTest extends TestCase
{
    public function testInvokeCreatesIncidentAndReturnsUuid(): void
    {
        $persistedIncident = null;
        $persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $persistenceManager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(static function (IncidentModel $incident) use (&$persistedIncident): void {
                $persistedIncident = $incident;
            });
        $persistenceManager->expects(self::once())
            ->method('flush');

        $dispatchedEvent = null;
        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (CreatedEvent $event) use (&$dispatchedEvent): void {
                $dispatchedEvent = $event;
            });

        $handler = new CreateIncidentCommandHandler($persistenceManager, $eventBus);

        $command = new CreateIncidentCommand(
            title: 'Database outage',
            description: 'PostgreSQL is not responding',
            severity: IncidentSeverityEnum::critical,
            affectedServiceNames: ['postgresql', 'api'],
        );

        $result = ($handler)($command);

        self::assertInstanceOf(Uuid::class, $result);
        self::assertNotNull($persistedIncident);
        self::assertSame('Database outage', $persistedIncident->getTitle());
        self::assertSame('PostgreSQL is not responding', $persistedIncident->getDescription());
        self::assertSame(IncidentSeverityEnum::critical, $persistedIncident->getSeverity());

        // Проверка события
        self::assertNotNull($dispatchedEvent);
        self::assertSame('Database outage', $dispatchedEvent->getTitle());
        self::assertSame(IncidentSeverityEnum::critical, $dispatchedEvent->getSeverity());
    }

    public function testInvokeCreatesIncidentWithMinimalData(): void
    {
        $persistedIncident = null;
        $persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $persistenceManager->method('persist')
            ->willReturnCallback(static function (IncidentModel $incident) use (&$persistedIncident): void {
                $persistedIncident = $incident;
            });

        $eventBus = $this->createMock(EventBusInterface::class);
        $eventBus->expects(self::once())
            ->method('dispatch');

        $handler = new CreateIncidentCommandHandler($persistenceManager, $eventBus);

        $command = new CreateIncidentCommand(
            title: 'Minor issue',
        );

        ($handler)($command);

        self::assertNotNull($persistedIncident);
        self::assertSame('Minor issue', $persistedIncident->getTitle());
        self::assertNull($persistedIncident->getDescription());
        self::assertSame(IncidentSeverityEnum::minor, $persistedIncident->getSeverity());
        self::assertSame([], $persistedIncident->getAffectedServiceNames());
    }
}
