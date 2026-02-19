<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Repository\ServiceStatus;

use Common\Exception\NotFoundExceptionInterface;
use Common\Module\Health\Domain\Entity\ServiceStatusModel;
use Symfony\Component\Uid\Uuid;

/**
 * Интерфейс репозитория для хранения статусов сервисов.
 */
interface ServiceStatusRepositoryInterface
{
    /**
     * Получить статус сервиса по ID или UUID.
     *
     * @throws NotFoundExceptionInterface
     */
    public function getById(?int $id = null, ?Uuid $uuid = null): ServiceStatusModel;

    /**
     * Найти один статус по критериям.
     */
    public function getOneByCriteria(ServiceStatusCriteriaInterface $criteria): ?ServiceStatusModel;

    /**
     * Получить все статусы по критериям.
     *
     * @return ServiceStatusModel[]
     */
    public function getByCriteria(ServiceStatusCriteriaInterface $criteria): array;

    /**
     * Получить количество записей по критериям.
     */
    public function getCountByCriteria(ServiceStatusCriteriaInterface $criteria): int;

    /**
     * Проверить существование по критериям.
     */
    public function exists(ServiceStatusCriteriaInterface $criteria): bool;

    /**
     * Сохранить статус сервиса.
     */
    public function save(ServiceStatusModel $serviceStatus): void;

    /**
     * Удалить статус сервиса (hard-delete).
     */
    public function delete(ServiceStatusModel $serviceStatus): void;
}
