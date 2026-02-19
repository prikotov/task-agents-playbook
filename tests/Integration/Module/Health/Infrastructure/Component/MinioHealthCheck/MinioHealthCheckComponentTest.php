<?php

declare(strict_types=1);

namespace Common\Test\Integration\Module\Health\Infrastructure\Component\MinioHealthCheck;

use Aws\S3\S3Client;
use Common\Component\Test\IntegrationTestCase;
use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use Common\Module\Health\Infrastructure\Component\MinioHealthCheck\MinioHealthCheckComponent;
use Common\Module\Health\Infrastructure\Component\MinioHealthCheck\MinioHealthCheckComponentInterface;
use Common\Module\Health\Infrastructure\Service\HealthChecker\MinioHealthCheckerService;
use Psr\Log\LoggerInterface;

final class MinioHealthCheckComponentTest extends IntegrationTestCase
{
    private MinioHealthCheckComponentInterface $component;
    private MinioHealthCheckerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var LoggerInterface $logger */
        $logger = self::getContainer()->get('logger');

        // Создаём S3Client из переменных окружения
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => $_ENV['STORAGE_S3_REGION'] ?? 'us-east-1',
            'endpoint' => $_ENV['STORAGE_S3_ENDPOINT'] ?? 'http://minio:9000',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $_ENV['STORAGE_S3_KEY'] ?? 'minioadmin',
                'secret' => $_ENV['STORAGE_S3_SECRET'] ?? 'minioadmin',
            ],
            'http' => [
                'connect_timeout' => (float) ($_ENV['STORAGE_S3_CONNECT_TIMEOUT'] ?? 3),
                'timeout' => 5.0,
            ],
        ]);

        $bucket = $_ENV['STORAGE_S3_BUCKET_SOURCE'] ?? 'task-source';

        $this->component = new MinioHealthCheckComponent($s3Client, $logger, $bucket);
        $this->service = new MinioHealthCheckerService($this->component);
    }

    public function testComponentCheckReturnsOperational(): void
    {
        $result = $this->component->check();

        self::assertTrue($result->isOperational());
        self::assertSame(ServiceStatusEnum::operational, $result->status);
        self::assertStringContainsString('MinIO', $result->message);
        self::assertNotNull($result->responseTimeMs);
        self::assertGreaterThan(0, $result->responseTimeMs);
    }

    public function testServiceGetNameReturnsMinio(): void
    {
        self::assertSame('minio', $this->service->getName());
    }

    public function testServiceCheckReturnsOperational(): void
    {
        $result = $this->service->check();

        self::assertTrue($result->isOperational());
        self::assertSame(ServiceStatusEnum::operational, $result->status);
    }

    public function testComponentHasCheckedAtTimestamp(): void
    {
        $result = $this->component->check();

        self::assertNotNull($result->checkedAt);
    }

    public function testServiceImplementsCheckHealthServiceInterface(): void
    {
        self::assertInstanceOf(
            \Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface::class,
            $this->service
        );
    }
}
