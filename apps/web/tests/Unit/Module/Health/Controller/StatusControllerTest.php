<?php

declare(strict_types=1);

namespace Web\Test\Unit\Module\Health\Controller;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Health\Application\Dto\ServiceHealthDto;
use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use Common\Module\Health\Application\Enum\ServiceStatusEnum;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Web\Module\Health\Controller\StatusController;
use Web\Module\Health\Route\StatusRoute;

final class StatusControllerTest extends TestCase
{
    private QueryBusComponentInterface&MockObject $queryBus;
    private CacheInterface&MockObject $cache;
    private StatusRoute $statusRoute;
    private StatusController $controller;

    protected function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusComponentInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->statusRoute = new StatusRoute();
        $this->controller = new StatusController(
            $this->queryBus,
            $this->cache,
            $this->statusRoute,
        );
    }

    public function testGetCategoryLabelForInfrastructure(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getCategoryLabel');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, ServiceCategoryEnum::infrastructure);

        $this->assertSame('Infrastructure', $result);
    }

    public function testGetCategoryLabelForLlm(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getCategoryLabel');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, ServiceCategoryEnum::llm);

        $this->assertSame('LLM Providers', $result);
    }

    public function testGetCategoryLabelForExternalApi(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getCategoryLabel');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, ServiceCategoryEnum::externalApi);

        $this->assertSame('External APIs', $result);
    }

    public function testGetCategoryLabelForCliTool(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getCategoryLabel');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, ServiceCategoryEnum::cliTool);

        $this->assertSame('CLI Tools', $result);
    }

    public function testGroupServicesByCategoryGroupsCorrectly(): void
    {
        $services = [
            new ServiceHealthDto('PostgreSQL', ServiceStatusEnum::operational, ServiceCategoryEnum::infrastructure),
            new ServiceHealthDto('Ollama', ServiceStatusEnum::operational, ServiceCategoryEnum::llm),
            new ServiceHealthDto('T-Bank', ServiceStatusEnum::operational, ServiceCategoryEnum::externalApi),
            new ServiceHealthDto('yt-dlp', ServiceStatusEnum::operational, ServiceCategoryEnum::cliTool),
        ];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('groupServicesByCategory');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $services);

        $this->assertArrayHasKey('Infrastructure', $result);
        $this->assertArrayHasKey('LLM Providers', $result);
        $this->assertArrayHasKey('External APIs', $result);
        $this->assertArrayHasKey('CLI Tools', $result);

        $this->assertCount(1, $result['Infrastructure']);
        $this->assertCount(1, $result['LLM Providers']);
        $this->assertCount(1, $result['External APIs']);
        $this->assertCount(1, $result['CLI Tools']);
    }

    public function testGroupServicesByCategoryGroupsMultipleInSameCategory(): void
    {
        $services = [
            new ServiceHealthDto('PostgreSQL', ServiceStatusEnum::operational, ServiceCategoryEnum::infrastructure),
            new ServiceHealthDto('RabbitMQ', ServiceStatusEnum::operational, ServiceCategoryEnum::infrastructure),
            new ServiceHealthDto('MinIO', ServiceStatusEnum::degraded, ServiceCategoryEnum::infrastructure),
        ];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('groupServicesByCategory');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $services);

        $this->assertArrayHasKey('Infrastructure', $result);
        $this->assertCount(3, $result['Infrastructure']);
    }
}
