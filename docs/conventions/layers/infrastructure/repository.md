# Репозиторий (Repository)

**Репозиторий** — инфраструктурная реализация доменного репозитория, которая скрывает работу с ORM/БД.

> **Фильтрация:** Для изоляции условий выборки используется [CriteriaMapper](criteria-mapper.md).

## Общие правила

1. Каждый репозиторий наследует `ServiceEntityRepository` и реализует доменный интерфейс `{EntityName}RepositoryInterface`.
2. Репозиторий не содержит условных запросов напрямую; все фильтры строятся через [CriteriaMapper](criteria-mapper.md).
3. Репозиторий оперирует только доменными сущностями и критериями; никаких зависимостей из Application/Presentation.
4. Исключения ORM маппятся в `NotFoundException` или `InfrastructureException`.

## Зависимости

- Разрешено: `ManagerRegistry`, [CriteriaMapper](criteria-mapper.md), доменные сущности и критерии, сервисы Doctrine.
- Запрещено: сервисы Application/Presentation, внешние API.

## Расположение

```
Common\Module\{ModuleName}\Infrastructure\Repository\{Entity}\{Entity}Repository.php
```

## Как используем

- В Application слое используем только доменный интерфейс; инфраструктурная реализация не просачивается наружу.

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Infrastructure\Repository\Project;

use Common\Exception\InfrastructureException;
use Common\Exception\NotFoundException;
use Common\Module\Project\Domain\Entity\ProjectModel;
use Common\Module\Project\Domain\Repository\Project\ProjectCriteriaInterface;
use Common\Module\Project\Domain\Repository\Project\ProjectRepositoryInterface;
use Common\Module\Project\Infrastructure\Repository\Project\Criteria\CriteriaMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

final class ProjectRepository extends ServiceEntityRepository implements ProjectRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CriteriaMapper $criteriaMapper,
    ) {
        parent::__construct($registry, ProjectModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getById(?int $id = null, ?Uuid $uuid = null): ProjectModel
    {
        if ($id === null && $uuid === null) {
            throw new InvalidArgumentException(
                sprintf('Either an ID or a UUID must be provided for entity %s.', $this->getEntityName()),
            );
        }

        if ($id !== null) {
            return $this->find($id) ?? throw new NotFoundException(sprintf(
                'Cannot find %s with id %s',
                $this->getEntityName(),
                $id,
            ));
        }

        if ($uuid !== null) {
            return $this->createQueryBuilder('p')
                ->andWhere('p.uuid = :uuid')
                ->setParameter('uuid', $uuid, UuidType::NAME)
                ->getQuery()
                ->getOneOrNullResult() ?? throw new NotFoundException(sprintf(
                    'Cannot find %s with uuid %s',
                    $this->getEntityName(),
                    $uuid,
                ));
        }

        throw new NotFoundException(sprintf('%s not found', $this->getEntityName()));
    }

    public function getOneByCriteria(ProjectCriteriaInterface $criteria): ?ProjectModel
    {
        return $this
            ->getQueryBuilderByCriteria($criteria)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * @inheritDoc
     */
    public function getByCriteria(ProjectCriteriaInterface $criteria): array
    {
        return $this
            ->getQueryBuilderByCriteria($criteria)
            ->getQuery()
            ->getResult();
    }

    private function getQueryBuilderByCriteria(ProjectCriteriaInterface $criteria): QueryBuilder
    {
        try {
            return $this->criteriaMapper->map($this, $criteria);
        } catch (QueryException $exception) {
            throw new InfrastructureException(
                message: sprintf('Failed to build query for %s: %s', $this->getEntityName(), $exception->getMessage()),
                previous: $exception,
            );
        }
    }
}
```
