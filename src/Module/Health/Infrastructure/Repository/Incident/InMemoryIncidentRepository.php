<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Repository\Incident;

use Common\Exception\NotFoundException;
use Common\Module\Health\Domain\Entity\IncidentModel;
use Common\Module\Health\Domain\Enum\IncidentStatusEnum;
use Common\Module\Health\Domain\Repository\Incident\Criteria\IncidentFindCriteria;
use Common\Module\Health\Domain\Repository\Incident\IncidentCriteriaInterface;
use Common\Module\Health\Domain\Repository\Incident\IncidentRepositoryInterface;
use InvalidArgumentException;
use Override;
use ReflectionClass;
use Symfony\Component\Uid\Uuid;

/**
 * In-memory реализация репозитория инцидентов для тестов.
 */
final class InMemoryIncidentRepository implements IncidentRepositoryInterface
{
    /** @var array<int, IncidentModel> */
    private array $storage = [];

    /** @var array<string, int> */
    private array $uuidIndex = [];

    private int $idSequence = 1;

    #[Override]
    public function getById(?int $id = null, ?Uuid $uuid = null): IncidentModel
    {
        if ($id === null && $uuid === null) {
            throw new InvalidArgumentException(
                'Either an ID or a UUID must be provided for IncidentModel.',
            );
        }

        if ($id !== null) {
            return $this->storage[$id] ?? throw new NotFoundException(sprintf(
                'Cannot find IncidentModel with id %s',
                $id,
            ));
        }

        $uuidString = $uuid->toRfc4122();
        $resolvedId = $this->uuidIndex[$uuidString] ?? null;
        if ($resolvedId === null) {
            throw new NotFoundException(sprintf(
                'Cannot find IncidentModel with uuid %s',
                $uuidString,
            ));
        }

        return $this->storage[$resolvedId];
    }

    #[Override]
    public function getOneByCriteria(IncidentCriteriaInterface $criteria): ?IncidentModel
    {
        $results = $this->getByCriteria($criteria);
        return $results[0] ?? null;
    }

    #[Override]
    public function getByCriteria(IncidentCriteriaInterface $criteria): array
    {
        if (!($criteria instanceof IncidentFindCriteria)) {
            return array_values($this->storage);
        }

        $results = [];

        foreach ($this->storage as $incident) {
            if ($this->matchesCriteria($incident, $criteria)) {
                $results[] = $incident;
            }
        }

        // Sort by insTs DESC by default
        usort($results, static fn (IncidentModel $a, IncidentModel $b) => $b->getInsTs() <=> $a->getInsTs());

        // Apply offset and limit
        $offset = $criteria->getOffset();
        $limit = $criteria->getLimit();

        if ($offset !== null) {
            $results = array_slice($results, $offset);
        }

        if ($limit !== null) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    #[Override]
    public function getCountByCriteria(IncidentCriteriaInterface $criteria): int
    {
        return count($this->getByCriteria($criteria));
    }

    #[Override]
    public function exists(IncidentCriteriaInterface $criteria): bool
    {
        foreach ($this->storage as $incident) {
            if ($this->matchesCriteria($incident, $criteria)) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function save(IncidentModel $incident): void
    {
        // Set ID via reflection for new entities (when id is not set yet)
        $reflection = new ReflectionClass($incident);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $currentId = $idProperty->getValue($incident);
        if ($currentId === null) {
            $idProperty->setValue($incident, $this->idSequence++);
        }

        $this->storage[$incident->getId()] = $incident;
        $this->uuidIndex[$incident->getUuid()->toRfc4122()] = $incident->getId();
    }

    #[Override]
    public function delete(IncidentModel $incident): void
    {
        $id = $incident->getId();
        unset($this->storage[$id]);
        unset($this->uuidIndex[$incident->getUuid()->toRfc4122()]);
    }

    /**
     * Clears all stored incidents.
     */
    public function clear(): void
    {
        $this->storage = [];
        $this->uuidIndex = [];
        $this->idSequence = 1;
    }

    private function matchesCriteria(IncidentModel $incident, IncidentCriteriaInterface $criteria): bool
    {
        if (!($criteria instanceof IncidentFindCriteria)) {
            return true;
        }

        if (!$this->matchesStatus($incident, $criteria)) {
            return false;
        }

        if (!$this->matchesSeverity($incident, $criteria)) {
            return false;
        }

        if (!$this->matchesServiceName($incident, $criteria)) {
            return false;
        }

        if (!$this->matchesActiveOnly($incident, $criteria)) {
            return false;
        }

        return true;
    }

    private function matchesStatus(IncidentModel $incident, IncidentFindCriteria $criteria): bool
    {
        $status = $criteria->getStatus();
        if ($status !== null && $incident->getStatus() !== $status) {
            return false;
        }

        return true;
    }

    private function matchesSeverity(IncidentModel $incident, IncidentFindCriteria $criteria): bool
    {
        $severity = $criteria->getSeverity();
        if ($severity !== null && $incident->getSeverity() !== $severity) {
            return false;
        }

        return true;
    }

    private function matchesServiceName(IncidentModel $incident, IncidentFindCriteria $criteria): bool
    {
        $serviceName = $criteria->getServiceName();
        if ($serviceName === null) {
            return true;
        }

        foreach ($incident->getAffectedServiceNames() as $affectedService) {
            if ($affectedService->value === $serviceName) {
                return true;
            }
        }

        return false;
    }

    private function matchesActiveOnly(IncidentModel $incident, IncidentFindCriteria $criteria): bool
    {
        if ($criteria->isActiveOnly() && $incident->getStatus() === IncidentStatusEnum::resolved) {
            return false;
        }

        return true;
    }
}
