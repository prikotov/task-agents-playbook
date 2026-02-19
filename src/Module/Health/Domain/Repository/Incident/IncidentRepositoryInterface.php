<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Repository\Incident;

use Common\Exception\NotFoundExceptionInterface;
use Common\Module\Health\Domain\Entity\IncidentModel;
use Symfony\Component\Uid\Uuid;

/**
 * Интерфейс репозитория для хранения инцидентов.
 */
interface IncidentRepositoryInterface
{
    /**
     * Получить инцидент по ID или UUID.
     *
     * @throws NotFoundExceptionInterface
     */
    public function getById(?int $id = null, ?Uuid $uuid = null): IncidentModel;

    /**
     * Найти один инцидент по критериям.
     */
    public function getOneByCriteria(IncidentCriteriaInterface $criteria): ?IncidentModel;

    /**
     * Получить все инциденты по критериям.
     *
     * @return IncidentModel[]
     */
    public function getByCriteria(IncidentCriteriaInterface $criteria): array;

    /**
     * Получить количество записей по критериям.
     */
    public function getCountByCriteria(IncidentCriteriaInterface $criteria): int;

    /**
     * Проверить существование по критериям.
     */
    public function exists(IncidentCriteriaInterface $criteria): bool;

    /**
     * Сохранить инцидент.
     */
    public function save(IncidentModel $incident): void;

    /**
     * Удалить инцидент (hard-delete).
     */
    public function delete(IncidentModel $incident): void;
}
