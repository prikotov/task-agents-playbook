<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Application\UseCase\Query\Incident\GetIncidentList;

use Common\Application\Dto\PaginationDto;
use Common\Application\Dto\SortDto;
use Common\Application\Enum\SortDirectionEnum;
use Common\Application\Mapper\SortDtoToOrderMapper;
use Common\Module\Health\Application\Enum\IncidentSeverityEnum;
use Common\Module\Health\Application\Enum\IncidentStatusEnum;
use Common\Module\Health\Application\Mapper\IncidentDtoMapper;
use Common\Module\Health\Application\Mapper\IncidentSeverityMapper;
use Common\Module\Health\Application\Mapper\IncidentStatusMapper;
use Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList\GetIncidentListQuery;
use Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList\GetIncidentListQueryHandler;
use Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList\IncidentListDto;
use Common\Module\Health\Domain\Entity\IncidentModel;
use Common\Module\Health\Domain\Enum\IncidentSeverityEnum as DomainIncidentSeverityEnum;
use Common\Module\Health\Domain\Enum\IncidentStatusEnum as DomainIncidentStatusEnum;
use Common\Module\Health\Domain\Repository\Incident\IncidentRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * @covers \Common\Module\Health\Application\UseCase\Query\Incident\GetIncidentList\GetIncidentListQueryHandler
 */
final class GetIncidentListQueryHandlerTest extends TestCase
{
    private IncidentDtoMapper $mapper;
    private SortDtoToOrderMapper $sortMapper;

    protected function setUp(): void
    {
        $this->mapper = new IncidentDtoMapper(
            new IncidentStatusMapper(),
            new IncidentSeverityMapper(),
        );
        $this->sortMapper = new SortDtoToOrderMapper();
    }

    public function testInvokeReturnsIncidentListDto(): void
    {
        $incidents = [
            $this->createIncident('Incident 1', DomainIncidentStatusEnum::investigating),
            $this->createIncident('Incident 2', DomainIncidentStatusEnum::identified),
        ];

        $repository = $this->createMock(IncidentRepositoryInterface::class);
        $repository->method('getByCriteria')->willReturn($incidents);
        $repository->method('getCountByCriteria')->willReturn(2);

        $handler = new GetIncidentListQueryHandler($repository, $this->mapper, $this->sortMapper);

        $query = new GetIncidentListQuery(
            pagination: new PaginationDto(limit: 20, offset: 0),
        );

        $result = ($handler)($query);

        self::assertInstanceOf(IncidentListDto::class, $result);
        self::assertCount(2, $result->items);
        self::assertSame(2, $result->total);
        self::assertSame(1, $result->page);
        self::assertSame(20, $result->perPage);
    }

    public function testInvokeWithFilters(): void
    {
        $incidents = [
            $this->createIncident('Critical Incident', DomainIncidentStatusEnum::investigating),
        ];

        $repository = $this->createMock(IncidentRepositoryInterface::class);
        $repository->method('getByCriteria')->willReturn($incidents);
        $repository->method('getCountByCriteria')->willReturn(1);

        $handler = new GetIncidentListQueryHandler($repository, $this->mapper, $this->sortMapper);

        $query = new GetIncidentListQuery(
            status: IncidentStatusEnum::investigating,
            severity: IncidentSeverityEnum::critical,
            activeOnly: true,
            pagination: new PaginationDto(limit: 10, offset: 0),
        );

        $result = ($handler)($query);

        self::assertCount(1, $result->items);
        self::assertSame('Critical Incident', $result->items[0]->title);
    }

    public function testInvokeWithSort(): void
    {
        $incidents = [
            $this->createIncident('Incident A', DomainIncidentStatusEnum::investigating),
        ];

        $repository = $this->createMock(IncidentRepositoryInterface::class);
        $repository->method('getByCriteria')->willReturn($incidents);
        $repository->method('getCountByCriteria')->willReturn(1);

        $handler = new GetIncidentListQueryHandler($repository, $this->mapper, $this->sortMapper);

        $query = new GetIncidentListQuery(
            pagination: new PaginationDto(limit: 20, offset: 0),
            sort: new SortDto(['title' => SortDirectionEnum::asc]),
        );

        $result = ($handler)($query);

        self::assertCount(1, $result->items);
    }

    public function testHasMoreReturnsTrueWhenMorePages(): void
    {
        $repository = $this->createMock(IncidentRepositoryInterface::class);
        $repository->method('getByCriteria')->willReturn([]);
        $repository->method('getCountByCriteria')->willReturn(50);

        $handler = new GetIncidentListQueryHandler($repository, $this->mapper, $this->sortMapper);

        $query = new GetIncidentListQuery(
            pagination: new PaginationDto(limit: 20, offset: 0),
        );

        $result = ($handler)($query);

        // hasMore = (page * perPage) < total => (1 * 20) < 50 => true
        self::assertTrue(($result->page * $result->perPage) < $result->total);
    }

    public function testHasMoreReturnsFalseWhenLastPage(): void
    {
        $repository = $this->createMock(IncidentRepositoryInterface::class);
        $repository->method('getByCriteria')->willReturn([]);
        $repository->method('getCountByCriteria')->willReturn(15);

        $handler = new GetIncidentListQueryHandler($repository, $this->mapper, $this->sortMapper);

        $query = new GetIncidentListQuery(
            pagination: new PaginationDto(limit: 20, offset: 0),
        );

        $result = ($handler)($query);

        // hasMore = (page * perPage) < total => (1 * 20) < 15 => false
        self::assertFalse(($result->page * $result->perPage) < $result->total);
    }

    private function createIncident(string $title, DomainIncidentStatusEnum $status): IncidentModel
    {
        $incident = $this->createMock(IncidentModel::class);
        $incident->method('getTitle')->willReturn($title);
        $incident->method('getStatus')->willReturn($status);
        $incident->method('getDescription')->willReturn(null);
        $incident->method('getSeverity')->willReturn(DomainIncidentSeverityEnum::minor);
        $incident->method('getAffectedServiceNames')->willReturn([]);
        $incident->method('getInsTs')->willReturn(new \DateTimeImmutable());
        $incident->method('getUpdTs')->willReturn(null);
        $incident->method('getResolvedAt')->willReturn(null);
        $incident->method('getUuid')->willReturn(Uuid::v7());

        return $incident;
    }
}
