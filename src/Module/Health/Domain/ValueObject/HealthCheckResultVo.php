<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\ValueObject;

use Common\Module\Health\Domain\Enum\ServiceStatusEnum;
use DateTimeImmutable;

/**
 * ValueObject для результата проверки здоровья сервиса.
 */
final readonly class HealthCheckResultVo
{
    private function __construct(
        public ServiceStatusEnum $status,
        public ?string $message = null,
        public ?DateTimeImmutable $checkedAt = null,
        public ?float $responseTimeMs = null,
    ) {
        $this->checkedAt ??= new DateTimeImmutable();
    }

    /**
     * Создаёт результат успешной проверки.
     */
    public static function createFromOperational(?string $message = null, ?float $responseTimeMs = null): self
    {
        return new self(
            status: ServiceStatusEnum::operational,
            message: $message,
            responseTimeMs: $responseTimeMs,
        );
    }

    /**
     * Создаёт результат деградации сервиса.
     */
    public static function createFromDegraded(string $message, ?float $responseTimeMs = null): self
    {
        return new self(
            status: ServiceStatusEnum::degraded,
            message: $message,
            responseTimeMs: $responseTimeMs,
        );
    }

    /**
     * Создаёт результат недоступности сервиса.
     */
    public static function createFromOutage(string $message, ?float $responseTimeMs = null): self
    {
        return new self(
            status: ServiceStatusEnum::outage,
            message: $message,
            responseTimeMs: $responseTimeMs,
        );
    }

    /**
     * Создаёт результат обслуживания сервиса.
     */
    public static function createFromMaintenance(?string $message = null): self
    {
        return new self(
            status: ServiceStatusEnum::maintenance,
            message: $message ?? 'Service is under maintenance',
        );
    }

    /**
     * Возвращает true, если сервис работает нормально.
     */
    public function isOperational(): bool
    {
        return $this->status === ServiceStatusEnum::operational;
    }

    /**
     * Возвращает true, если сервис требует внимания.
     */
    public function requiresAttention(): bool
    {
        return $this->status === ServiceStatusEnum::degraded
            || $this->status === ServiceStatusEnum::outage;
    }

    /**
     * Сравнивает два ValueObject по значениям.
     */
    public function equals(self $other): bool
    {
        return $this->status === $other->status
            && $this->message === $other->message
            && $this->checkedAt == $other->checkedAt
            && $this->responseTimeMs === $other->responseTimeMs;
    }
}
