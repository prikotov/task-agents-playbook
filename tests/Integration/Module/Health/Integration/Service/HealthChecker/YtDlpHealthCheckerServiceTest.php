<?php

declare(strict_types=1);

namespace Common\Test\Integration\Module\Health\Integration\Service\HealthChecker;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Component\Test\IntegrationTestCase;
use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use Common\Module\Health\Integration\Service\HealthChecker\YtDlpHealthCheckerService;
use Common\Module\Source\Application\Dto\YtDlpHealthDto;
use Common\Module\Source\Application\UseCase\Query\CheckYtDlpHealth\CheckYtDlpHealthQuery;

/**
 * @group ytdlp
 */
final class YtDlpHealthCheckerServiceTest extends IntegrationTestCase
{
    private CheckHealthServiceInterface $service;
    private QueryBusComponentInterface $queryBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBus = self::getContainer()->get(QueryBusComponentInterface::class);
        $this->service = self::getContainer()->get(YtDlpHealthCheckerService::class);
    }

    public function testServiceImplementsCheckHealthServiceInterface(): void
    {
        self::assertInstanceOf(CheckHealthServiceInterface::class, $this->service);
    }

    public function testServiceReturnsCorrectName(): void
    {
        self::assertSame('yt-dlp', $this->service->getName());
    }

    public function testServiceCheckReturnsResult(): void
    {
        $result = $this->service->check();

        self::assertNotNull($result);
        self::assertNotNull($result->status);
        self::assertNotNull($result->checkedAt);
        self::assertNotNull($result->responseTimeMs);
        self::assertGreaterThan(0, $result->responseTimeMs);
    }

    public function testServiceReturnsOperationalWhenYtDlpAvailable(): void
    {
        // Пропускаем тест если yt-dlp не установлен в тестовом окружении
        $ytDlpBinPath = self::getContainer()->getParameter('module.source.yt_dlp.bin_path');
        $binaryPath = rtrim($ytDlpBinPath, '/') . '/yt-dlp';
        if (!file_exists($binaryPath) && !file_exists($binaryPath . '.exe')) {
            self::markTestSkipped('yt-dlp binary not available in test environment');
        }

        $result = $this->service->check();

        self::assertTrue($result->isOperational(), 'yt-dlp health check failed: ' . ($result->message ?? 'unknown error'));
        self::assertSame(ServiceStatusEnum::operational, $result->status);
        self::assertStringContainsString('yt-dlp', $result->message ?? '');
        self::assertStringContainsString('version', $result->message ?? '');
    }

    public function testQueryBusReturnsYtDlpHealthDto(): void
    {
        $result = $this->queryBus->query(new CheckYtDlpHealthQuery());

        self::assertInstanceOf(YtDlpHealthDto::class, $result);
        self::assertIsBool($result->isHealthy);
    }
}
