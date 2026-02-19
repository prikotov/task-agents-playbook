<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Domain\Entity;

use Common\Module\Health\Domain\Entity\IncidentModel;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum;
use Common\Module\Health\Domain\Enum\IncidentStatusEnum;
use Common\Module\Health\Domain\ValueObject\ServiceNameVo;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Domain\Entity\IncidentModel
 */
final class IncidentModelTest extends TestCase
{
    public function testCreateWithMinimalData(): void
    {
        $incident = new IncidentModel(title: 'Database outage');

        self::assertSame('Database outage', $incident->getTitle());
        self::assertNull($incident->getDescription());
        self::assertSame(IncidentStatusEnum::investigating, $incident->getStatus());
        self::assertSame(IncidentSeverityEnum::minor, $incident->getSeverity());
        self::assertEmpty($incident->getAffectedServiceNames());
        self::assertNull($incident->getResolvedAt());
        self::assertFalse($incident->isResolved());
        self::assertTrue($incident->isActive());
        self::assertFalse($incident->isCritical());
    }

    public function testCreateWithAllData(): void
    {
        $incident = new IncidentModel(
            title: 'Critical API downtime',
            description: 'API service is not responding',
            severity: IncidentSeverityEnum::critical,
            affectedServiceNames: ['api-gateway', 'auth-service'],
        );

        self::assertSame('Critical API downtime', $incident->getTitle());
        self::assertSame('API service is not responding', $incident->getDescription());
        self::assertSame(IncidentStatusEnum::investigating, $incident->getStatus());
        self::assertSame(IncidentSeverityEnum::critical, $incident->getSeverity());
        self::assertCount(2, $incident->getAffectedServiceNames());
        self::assertSame('api-gateway', $incident->getAffectedServiceNames()[0]->value);
        self::assertSame('auth-service', $incident->getAffectedServiceNames()[1]->value);
        self::assertTrue($incident->isCritical());
    }

    public function testUpdateStatus(): void
    {
        $incident = new IncidentModel(title: 'Test incident');
        self::assertSame(IncidentStatusEnum::investigating, $incident->getStatus());

        $incident->updateStatus(IncidentStatusEnum::identified);
        self::assertSame(IncidentStatusEnum::identified, $incident->getStatus());

        $incident->updateStatus(IncidentStatusEnum::monitoring);
        self::assertSame(IncidentStatusEnum::monitoring, $incident->getStatus());
    }

    public function testResolveSetsStatusAndResolvedAt(): void
    {
        $incident = new IncidentModel(title: 'Test incident');

        self::assertFalse($incident->isResolved());
        self::assertTrue($incident->isActive());
        self::assertNull($incident->getResolvedAt());

        $incident->resolve();

        self::assertTrue($incident->isResolved());
        self::assertFalse($incident->isActive());
        self::assertNotNull($incident->getResolvedAt());
        self::assertSame(IncidentStatusEnum::resolved, $incident->getStatus());
    }

    public function testResolveIsIdempotent(): void
    {
        $incident = new IncidentModel(title: 'Test incident');
        $incident->resolve();

        $resolvedAt = $incident->getResolvedAt();
        self::assertNotNull($resolvedAt);

        // Second resolve should not change anything
        $incident->resolve();
        self::assertSame($resolvedAt, $incident->getResolvedAt());
    }

    public function testAddAffectedService(): void
    {
        $incident = new IncidentModel(title: 'Test incident');
        self::assertEmpty($incident->getAffectedServiceNames());

        $incident->addAffectedService(new ServiceNameVo('postgresql'));
        self::assertCount(1, $incident->getAffectedServiceNames());
        self::assertSame('postgresql', $incident->getAffectedServiceNames()[0]->value);

        $incident->addAffectedService(new ServiceNameVo('rabbitmq'));
        self::assertCount(2, $incident->getAffectedServiceNames());
    }

    public function testAddAffectedServiceDoesNotDuplicate(): void
    {
        $incident = new IncidentModel(title: 'Test incident');

        $incident->addAffectedService(new ServiceNameVo('postgresql'));
        $incident->addAffectedService(new ServiceNameVo('postgresql'));

        self::assertCount(1, $incident->getAffectedServiceNames());
    }

    public function testRemoveAffectedService(): void
    {
        $incident = new IncidentModel(
            title: 'Test incident',
            affectedServiceNames: ['postgresql', 'rabbitmq'],
        );

        self::assertCount(2, $incident->getAffectedServiceNames());

        $incident->removeAffectedService(new ServiceNameVo('postgresql'));
        self::assertCount(1, $incident->getAffectedServiceNames());
        self::assertSame('rabbitmq', $incident->getAffectedServiceNames()[0]->value);
    }

    public function testSetSeverity(): void
    {
        $incident = new IncidentModel(title: 'Test incident');
        self::assertSame(IncidentSeverityEnum::minor, $incident->getSeverity());
        self::assertFalse($incident->isCritical());

        $incident->setSeverity(IncidentSeverityEnum::major);
        self::assertSame(IncidentSeverityEnum::major, $incident->getSeverity());
        self::assertFalse($incident->isCritical());

        $incident->setSeverity(IncidentSeverityEnum::critical);
        self::assertSame(IncidentSeverityEnum::critical, $incident->getSeverity());
        self::assertTrue($incident->isCritical());
    }

    public function testSetTitle(): void
    {
        $incident = new IncidentModel(title: 'Original title');
        self::assertSame('Original title', $incident->getTitle());

        $incident->setTitle('Updated title');
        self::assertSame('Updated title', $incident->getTitle());
    }

    public function testSetDescription(): void
    {
        $incident = new IncidentModel(title: 'Test incident');
        self::assertNull($incident->getDescription());

        $incident->setDescription('New description');
        self::assertSame('New description', $incident->getDescription());

        $incident->setDescription(null);
        self::assertNull($incident->getDescription());
    }

    public function testSetAffectedServiceNames(): void
    {
        $incident = new IncidentModel(title: 'Test incident');

        $incident->setAffectedServiceNames(['service-a', 'service-b']);

        self::assertCount(2, $incident->getAffectedServiceNames());
    }

    public function testIsCriticalReturnsTrueOnlyForCriticalSeverity(): void
    {
        $incident = new IncidentModel(
            title: 'Test',
            severity: IncidentSeverityEnum::minor,
        );
        self::assertFalse($incident->isCritical());

        $incident->setSeverity(IncidentSeverityEnum::major);
        self::assertFalse($incident->isCritical());

        $incident->setSeverity(IncidentSeverityEnum::critical);
        self::assertTrue($incident->isCritical());
    }
}
