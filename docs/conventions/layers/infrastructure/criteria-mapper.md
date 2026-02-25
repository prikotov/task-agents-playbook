# CriteriaMapper (Маппер критериев)

**CriteriaMapper** — инфраструктурный класс, изолирующий логику фильтрации от [Репозитория](repository.md). Преобразует доменный [Criteria](../domain/criteria.md) в Doctrine `QueryBuilder`.

> См. также: [Критерий (Criteria)](../domain/criteria.md), [Репозиторий (Repository)](repository.md), [Маппер (Mapper)](../../core_patterns/mapper.md)

## Структура

CriteriaMapper состоит из двух уровней:

### 1. CriteriaMapper (диспетчер)

Реализует маппинг `Criteria → QueryBuilder` через делегирование конкретному мапперу. Резолвит стратегию маппинга по runtime-типу критерия (`$criteria::class`).

**Расположение:** `Infrastructure/Repository/{Entity}/Criteria/CriteriaMapper.php`

### 2. Конкретный {CriteriaName}CriteriaMapper

Реализует стратегию маппинга для конкретного типа критерия.

**Расположение:** `Infrastructure/Repository/{Entity}/Criteria/Mapper/{CriteriaName}CriteriaMapper.php`

## Общие правила

### Для диспетчера CriteriaMapper

- Класс `final readonly`
- Метод `map(Repository $repository, CriteriaInterface $criteria): QueryBuilder`
- Резолвит стратегию по runtime-типу критерия
- Неизвестный тип критерия → `ConfigurationException`
- Делегирует маппинг конкретному `*CriteriaMapper`

### Для конкретного {CriteriaName}CriteriaMapper

- Класс `final readonly`
- Метод `map(Repository $repository, ConcreteCriteria $criteria): QueryBuilder` — реализует стратегию маппинга
- Всегда возвращает `QueryBuilder` (не `null`)
- Приватные `apply*()` методы для сложных фильтров (см. ниже)
- Для пагинации/сортировки используется `LimitOffsetSortCriteriaMapper`

## Зависимости

**Разрешено**:
- `LimitOffsetSortCriteriaMapper` через DI
- Доменные сущности, [Criteria](../domain/criteria.md), VO, Enum
- Другие мапперы Infrastructure слоя

**Запрещено**:
- Сервисы Application/Presentation слоёв
- Внешние API и HTTP-клиенты
- Прямое обращение к БД (только через QueryBuilder)

## Расположение

```
Common\Module\{ModuleName}\Infrastructure\Repository\{Entity}\Criteria\CriteriaMapper.php
Common\Module\{ModuleName}\Infrastructure\Repository\{Entity}\Criteria\Mapper\{CriteriaName}CriteriaMapper.php
```

> Диспетчер лежит в `Criteria/`, конкретные мапперы — в `Criteria/Mapper/`.

## Именование

### Диспетчер

- Имя класса: `CriteriaMapper`
- Файл: `CriteriaMapper.php` (в папке `Criteria/`, без подпапки `Mapper/`)
- Отличие от конкретных: расположение в `/Criteria/`, а не `/Criteria/Mapper/`

### Конкретный маппер

- Имя класса: `{CriteriaName}CriteriaMapper`
- `{CriteriaName}` — имя доменного критерия без суффикса `Criteria`
- Примеры:
  - `PaymentFindCriteria` → `PaymentFindCriteriaMapper`
  - `PaymentGatewayStatusCriteria` → `PaymentGatewayStatusCriteriaMapper`
  - `UserActiveCriteria` → `UserActiveCriteriaMapper`

### Соответствие критерий ↔ маппер

| Доменный критерий | Маппер |
|-------------------|--------|
| `{Entity}FindCriteria` | `{Entity}FindCriteriaMapper` |
| `{Entity}ActiveCriteria` | `{Entity}ActiveCriteriaMapper` |
| `{Entity}By{Property}Criteria` | `{Entity}By{Property}CriteriaMapper` |

## Пример: диспетчер CriteriaMapper

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

## Пример: простой CriteriaMapper

Для критериев с 1-3 простыми фильтрами декомпозиция не требуется:

```php
<?php

declare(strict_types=1);

namespace Common\Module\Llm\Infrastructure\Repository\Model\Criteria\Mapper;

use Common\Component\Repository\Criteria\Mapper\LimitOffsetSortCriteriaMapper;
use Common\Module\Llm\Domain\Repository\Model\Criteria\ModelFindCriteria;
use Common\Module\Llm\Infrastructure\Repository\Model\ModelRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\QueryBuilder;

final readonly class ModelFindCriteriaMapper
{
    public function __construct(
        private LimitOffsetSortCriteriaMapper $limitOffsetSortCriteriaMapper,
    ) {
    }

    public function map(
        ModelRepository $repository,
        ModelFindCriteria $criteria,
    ): QueryBuilder {
        $qb = $repository->createQueryBuilder('model');

        if (($provider = $criteria->getProvider()) !== null) {
            $qb->andWhere('model.provider = :provider')
                ->setParameter('provider', $provider->value, ParameterType::STRING);
        }

        if (($isActive = $criteria->getIsActive()) !== null) {
            $qb->andWhere('model.isActive = :isActive')
                ->setParameter('isActive', $isActive, ParameterType::BOOLEAN);
        }

        $criteriaObject = $this->limitOffsetSortCriteriaMapper->map($criteria);
        $qb->addCriteria($criteriaObject);

        return $qb;
    }
}
```

## Декомпозиция для сложных CriteriaMapper

Декомпозиция на `apply*()` методы обязательна при наличии любого из признаков:

- Метод `map()` содержит более 40 строк кода
- Более 5 независимых условий фильтрации
- Смешиваются разные типы фильтров: scope, search, attributes, date ranges, relations
- Появляются дублирующиеся JOIN-паттерны

> **Жёсткое ограничение:** PHPMD [`ExcessiveMethodLength`](../../../phpmd.xml) не позволит создать метод длиннее 80 строк. Декомпозиция на 40 строках — best practice для предотвращения предупреждений анализатора.

### Структура декомпозированного `map()`

```php
public function map(Repository $repository, Criteria $criteria): QueryBuilder
{
    $qb = $repository->createQueryBuilder('alias');

    $this->applyUserScopeFilters($qb, $criteria);
    $this->applySearchFilters($qb, $criteria);
    $this->applyAttributeFilters($qb, $criteria);
    $this->applyDateRangeFilters($qb, $criteria);
    $this->applyRelationFilters($qb, $criteria);

    $criteriaObject = $this->limitOffsetSortCriteriaMapper->map($criteria);
    $qb->addCriteria($criteriaObject);

    return $qb;
}
```

### Именование `apply*()` методов

| Группа | Имя метода | Пример содержимого |
|--------|------------|-------------------|
| Область видимости | `applyUserScopeFilters()` | userUuid, teamAdminUuid, teamUuid |
| Поиск | `applySearchFilters()` | search, email, username (LIKE/ILIKE) |
| Атрибуты | `applyAttributeFilters()` | status, currency, type, provider |
| Даты | `applyDateRangeFilters()` | from, to (insTs, updatedAt) |
| Связи | `applyRelationFilters()` | projectUuid, chatUuid (JOIN) |

### Правила для `apply*()` методов

- Метод всегда `private` и `void`
- В начале — guard clause для раннего выхода
- Один метод — одна логическая группа фильтров
- Параметры: `QueryBuilder $qb, Criteria $criteria`
- QueryBuilder мутируется, возвращаемого значения нет

## Пример: сложный CriteriaMapper с декомпозицией

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Infrastructure\Repository\Payment\Criteria\Mapper;

use Common\Component\Repository\Criteria\Mapper\LimitOffsetSortCriteriaMapper;
use Common\Module\Billing\Domain\Repository\Payment\Criteria\PaymentFindCriteria;
use Common\Module\Billing\Infrastructure\Repository\Payment\PaymentReadRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Types\UuidType;

final readonly class PaymentFindCriteriaMapper
{
    public function __construct(
        private LimitOffsetSortCriteriaMapper $limitOffsetSortCriteriaMapper,
    ) {
    }

    public function map(
        PaymentReadRepository $repository,
        PaymentFindCriteria $criteria,
    ): QueryBuilder {
        $qb = $repository->createQueryBuilder('payment');
        $qb->join('payment.user', 'u');

        $this->applyUserScopeFilters($qb, $criteria);
        $this->applyAttributeFilters($qb, $criteria);
        $this->applyDateRangeFilters($qb, $criteria);

        $criteriaObject = $this->limitOffsetSortCriteriaMapper->map($criteria);
        $qb->addCriteria($criteriaObject);

        return $qb;
    }

    private function applyUserScopeFilters(QueryBuilder $qb, PaymentFindCriteria $criteria): void
    {
        $userUuid = $criteria->getUserUuid();
        if ($userUuid === null) {
            return;
        }

        $qb->andWhere('u.uuid = :userUuid');
        $qb->setParameter('userUuid', $userUuid, UuidType::NAME);
    }

    private function applyDateRangeFilters(QueryBuilder $qb, PaymentFindCriteria $criteria): void
    {
        $from = $criteria->getFrom();
        if ($from !== null) {
            $qb->andWhere('payment.insTs >= :from');
            $qb->setParameter('from', $from->format('Y-m-d H:i:s'), ParameterType::STRING);
        }
    }

    private function applyAttributeFilters(QueryBuilder $qb, PaymentFindCriteria $criteria): void
    {
        // ... остальные фильтры
    }
}
```

## Чек-лист для ревью кода

- [ ] Диспетчер `CriteriaMapper` регистрирует все конкретные мапперы
- [ ] Неизвестный критерий выбрасывает `ConfigurationException`
- [ ] Имя маппера соответствует критерию: `{CriteriaName}Criteria` → `{CriteriaName}CriteriaMapper`
- [ ] Класс помечен как `final readonly`
- [ ] Метод `map()` не превышает 40 строк (для сложных — применена декомпозиция на `apply*()`)
- [ ] Все параметры QueryBuilder типизированы (`ParameterType::*`, `UuidType::NAME`, `ArrayParameterType::*`)
- [ ] Для пагинации и сортировки используется `LimitOffsetSortCriteriaMapper`
- [ ] Нет дублирующихся JOIN между `apply*()` методами
- [ ] Unit-тесты покрывают нетривиальную логику фильтрации
