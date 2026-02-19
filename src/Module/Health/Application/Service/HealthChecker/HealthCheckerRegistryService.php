<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Service\HealthChecker;

use Common\Module\Health\Domain\Service\HealthChecker\CheckHealthServiceInterface;
use InvalidArgumentException;

/**
 * Сервис-реестр для управления health checkers.
 */
final class HealthCheckerRegistryService
{
    /** @var array<string, CheckHealthServiceInterface> */
    private array $checkers = [];

    /**
     * @param iterable<CheckHealthServiceInterface> $checkers
     */
    public function __construct(iterable $checkers = [])
    {
        foreach ($checkers as $checker) {
            $this->register($checker);
        }
    }

    /**
     * Регистрирует health checker.
     */
    public function register(CheckHealthServiceInterface $checker): self
    {
        $name = $checker->getName();

        if (isset($this->checkers[$name])) {
            throw new InvalidArgumentException(
                sprintf('Health checker "%s" is already registered.', $name)
            );
        }

        $this->checkers[$name] = $checker;

        return $this;
    }

    /**
     * Возвращает health checker по имени сервиса.
     */
    public function getChecker(string $serviceName): ?CheckHealthServiceInterface
    {
        return $this->checkers[$serviceName] ?? null;
    }

    /**
     * Проверяет, зарегистрирован ли checker для сервиса.
     */
    public function hasChecker(string $serviceName): bool
    {
        return isset($this->checkers[$serviceName]);
    }

    /**
     * Возвращает все зарегистрированные checkers.
     *
     * @return array<string, CheckHealthServiceInterface>
     */
    public function getAllCheckers(): array
    {
        return $this->checkers;
    }

    /**
     * Возвращает имена всех зарегистрированных сервисов.
     *
     * @return string[]
     */
    public function getRegisteredServiceNames(): array
    {
        return array_keys($this->checkers);
    }

    /**
     * Удаляет checker по имени сервиса.
     */
    public function unregister(string $serviceName): self
    {
        unset($this->checkers[$serviceName]);

        return $this;
    }

    /**
     * Очищает все зарегистрированные checkers.
     */
    public function clear(): self
    {
        $this->checkers = [];

        return $this;
    }
}
