<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Service\HealthChecker;

use Common\Module\Health\Domain\Enum\ServiceCategoryEnum;
use Common\Module\Health\Domain\ValueObject\HealthCheckResultVo;

/**
 * Интерфейс сервиса для выполнения проверок здоровья.
 */
interface RunHealthChecksServiceInterface
{
    /**
     * Выполнить проверку здоровья по имени сервиса.
     */
    public function checkByName(string $name): HealthCheckResultVo;

    /**
     * Выполнить проверки всех сервисов категории.
     *
     * @return array<string, HealthCheckResultVo> имя сервиса => результат
     */
    public function checkByCategory(ServiceCategoryEnum $category): array;

    /**
     * Выполнить проверки всех сервисов.
     *
     * @return array<string, HealthCheckResultVo> имя сервиса => результат
     */
    public function checkAll(): array;
}
