# Репозиторий и CriteriaMapper (Repository with CriteriaMapper)

**Репозиторий** — инфраструктурная реализация доменного репозитория, которая скрывает работу с ORM/БД. Для изоляции фильтров используется **CriteriaMapper** (см. пример `Common\Module\Project\Infrastructure\Repository\Project\Criteria\CriteriaMapper`).

## Общие правила

1. Каждый репозиторий наследует `ServiceEntityRepository` и реализует доменный интерфейс `{EntityName}RepositoryInterface`.
2. Репозиторий не содержит условных запросов напрямую; все фильтры строятся через `CriteriaMapper`.
3. Для каждого доменного критерия существует отдельный mapper в пространстве `Infrastructure/Repository/{Entity}/Criteria/Mapper`.
4. Отсутствие mapper'а для используемого критерия — конфигурационная ошибка (`ConfigurationException`).
5. Репозиторий оперирует только доменными сущностями и критериями; никаких зависимостей из Application/Presentation.
6. Исключения ORM маппятся в `NotFoundException` или `InfrastructureException`.

## Зависимости

- Разрешено: `ManagerRegistry`, `CriteriaMapper`, доменные сущности и критерии, сервисы Doctrine.
- Запрещено: сервисы Application/Presentation, внешние API.

## Расположение

```
Common\Module\{ModuleName}\Infrastructure\Repository\{Entity}\{Entity}Repository.php
Common\Module\{ModuleName}\Infrastructure\Repository\{Entity}\Criteria\CriteriaMapper.php
Common\Module\{ModuleName}\Infrastructure\Repository\{Entity}\Criteria\Mapper\*Mapper.php
```

## Как используем

- При добавлении нового доменного критерия добавляем mapper и регистрируем его внутри `CriteriaMapper`.
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

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Infrastructure\Repository\Project\Criteria;

use Common\Exception\ConfigurationException;
use Common\Module\Project\Domain\Repository\Project\Criteria\ProjectFindCriteria;
use Common\Module\Project\Domain\Repository\Project\ProjectCriteriaInterface;
use Common\Module\Project\Infrastructure\Repository\Project\Criteria\Mapper\ProjectFindCriteriaMapper;
use Common\Module\Project\Infrastructure\Repository\Project\ProjectRepository;
use Doctrine\ORM\QueryBuilder;

final readonly class CriteriaMapper
{
    public function __construct(
        private ProjectFindCriteriaMapper $projectFindCriteriaMapper,
    ) {
    }

    public function map(
        ProjectRepository $repository,
        ProjectCriteriaInterface $criteria,
    ): QueryBuilder {
        return match ($criteria::class) {
            ProjectFindCriteria::class => $this->projectFindCriteriaMapper->map($repository, $criteria),
            default => throw new ConfigurationException('Mapper not found for ' . $criteria::class),
        };
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Infrastructure\Repository\Project\Criteria\Mapper;

use Common\Component\Repository\Criteria\Mapper\LimitOffsetSortCriteriaMapper;
use Common\Module\Project\Domain\Enum\ProjectStatusEnum;
use Common\Module\Project\Domain\Repository\Project\Criteria\ProjectFindCriteria;
use Common\Module\Project\Infrastructure\Repository\Project\ProjectRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UuidType;

final readonly class ProjectFindCriteriaMapper
{
    public function __construct(
        private LimitOffsetSortCriteriaMapper $limitOffsetSortCriteriaMapper,
    ) {
    }

    public function map(
        ProjectRepository $repository,
        ProjectFindCriteria $criteria,
    ): QueryBuilder {
        $qb = $repository->createQueryBuilder('project');

        if (($status = $criteria->getStatus()) !== null) {
            $qb->andWhere('project.status = :status')
                ->setParameter('status', $status->value, ParameterType::INTEGER);
        }

        if ($criteria->getStatuses() !== []) {
            $qb->andWhere($qb->expr()->in('project.status', ':statuses'))
                ->setParameter(
                    'statuses',
                    array_map(static fn (ProjectStatusEnum $value): int => $value->value, $criteria->getStatuses()),
                    ArrayParameterType::INTEGER,
                );
        }

        if (($userUuid = $criteria->getUserUuid()) !== null) {
            $qb->andWhere('project.ownerUuid = :userUuid')
                ->setParameter('userUuid', $userUuid, UuidType::NAME);
        }

        $criteriaObject = $this->limitOffsetSortCriteriaMapper->map($criteria);
        $qb->addCriteria($criteriaObject);

        return $qb;
    }
}
```
