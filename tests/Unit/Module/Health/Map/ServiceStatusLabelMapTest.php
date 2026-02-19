<?php

declare(strict_types=1);

namespace Tests\Unit\Module\Health\Map;

use Common\Module\Health\Application\Enum\ServiceStatusEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Web\Module\Health\Map\ServiceStatusLabelMap;

final class ServiceStatusLabelMapTest extends TestCase
{
    private ServiceStatusLabelMap $map;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(fn (string $id) => match ($id) {
                'health.dashboard.stats.operational' => 'Operational',
                'health.dashboard.stats.degraded' => 'Degraded',
                'health.dashboard.stats.outage' => 'Outage',
                'health.dashboard.stats.maintenance' => 'Maintenance',
                default => $id,
            });

        $this->map = new ServiceStatusLabelMap($translator);
    }

    public function testGetAssociationByValueOperational(): void
    {
        self::assertSame('Operational', $this->map->getAssociationByValue(ServiceStatusEnum::operational));
    }

    public function testGetAssociationByValueDegraded(): void
    {
        self::assertSame('Degraded', $this->map->getAssociationByValue(ServiceStatusEnum::degraded));
    }

    public function testGetAssociationByValueOutage(): void
    {
        self::assertSame('Outage', $this->map->getAssociationByValue(ServiceStatusEnum::outage));
    }

    public function testGetAssociationByValueMaintenance(): void
    {
        self::assertSame('Maintenance', $this->map->getAssociationByValue(ServiceStatusEnum::maintenance));
    }

    public function testGetAssociationByValueAllStatusesCovered(): void
    {
        foreach (ServiceStatusEnum::cases() as $status) {
            $result = $this->map->getAssociationByValue($status);
            self::assertNotEmpty($result);
        }
    }
}
