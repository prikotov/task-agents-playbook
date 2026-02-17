# Репозиторий (Repository)

**Репозиторий (Repository)** — контракт для сохранения и извлечения доменных сущностей из хранилища по доменным критериям.
Репозиторий изолирует доменную модель от деталей инфраструктуры и скрывает механику выборок (ORM, SQL и т.п.).

## Общие правила

- Репозиторий объявляется в `Domain\Repository\*` и работает на доменных сущностях (`*Model`).
- Интерфейс репозитория именуется `{EntityName}RepositoryInterface`.
- Обязательные методы:
  - `save(Entity $entity): void` — добавление/обновление сущности.
  - `getById(?int $id = null, ?Uuid $uuid = null): Entity` — загрузка по идентификатору или UUID, при отсутствии выбрасывает `NotFoundExceptionInterface`.
  - `getOneByCriteria(Criteria): ?Entity` — возвращает сущность или `null`.
  - `getByCriteria(Criteria): Entity[]` — всегда массив (возможно пустой), **никогда не `null`**.
  - `getCountByCriteria(Criteria): int` — подсчитать количество сущностей по критерию.
  - `delete(Entity $entity): void` — допускается только для hard-delete.
- Если в домене предусмотрен только **soft-delete**, метод `delete()` в репозитории не объявляется.
- При soft-delete используем бизнес-методы сущности (`markAsDeleted()`, `deactivate()` и др.).
- Для поддержки CQRS интерфейсы на чтение и запись рекомендуется разделять на `{EntityName}ReadRepositoryInterface` и `{EntityName}WriteRepositoryInterface`.
- Репозиторий не управляет Unit of Work (`flush`, `commit`). Контроль транзакции всегда на уровне CommandHandler/UseCase, чтобы обеспечить атомарность бизнес-операции.
- Репозиторий маппит исключения ORM/SDK в доменные: `NotFoundExceptionInterface` для отсутствия сущности, `InfrastructureExceptionInterface` для ошибок работы хранилища.
- Реализации интерфейса размещаются в слое [Infrastructure](../infrastructure.md). Интерфейс репозитория — часть домена, реализация — часть инфраструктуры.
- Правила построения инфраструктурных репозиториев и CriteriaMapper описаны в [разделе Infrastructure](../infrastructure/repository.md); при добавлении реализации следуем этому шаблону.
- Интерфейсы репозиториев зависят только от доменных типов (Entity/VO/Criteria). **Запрещены** ссылки на классы инфраструктуры и Application.

❗Инфраструктурные классы (Doctrine, PDO и т.п.) в домен не протекают.

Критерии (Criteria) инкапсулируют фильтры/сортировки/пагинацию для выборок. Репозитории принимают интерфейсы критериев вместо именованных методов. См. [criteria.md](../infrastructure/criteria.md).

## Расположение

- Интерфейс в слое [Domain](../domain.md):

```php
namespace Common\Module\{ModuleName}\Domain\Repository\{EntityName}\{EntityName}RepositoryInterface
```

- Реализация в слое [Infrastructure](../infrastructure.md):

```php
namespace Common\Module\{ModuleName}\Infrastructure\Repository\{EntityName}\{EntityName}Repository
```

## Пример

### Репозиторий
```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Domain\Repository\Payment;

use Common\Exception\NotFoundExceptionInterface;
use Common\Module\Billing\Domain\Entity\PaymentModel;
use Common\Module\Billing\Domain\Repository\Payment\PaymentCriteriaInterface;
use Symfony\Component\Uid\Uuid;

interface PaymentRepositoryInterface
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function getById(?int $id = null, ?Uuid $uuid = null): PaymentModel;

    public function getOneByCriteria(PaymentCriteriaInterface $criteria): ?PaymentModel;

    /**
     * @return PaymentModel[]
     */
    public function getByCriteria(PaymentCriteriaInterface $criteria): array;

    public function getCountByCriteria(PaymentCriteriaInterface $criteria): int;

    public function save(PaymentModel $model): void;
}
```

### Использование в CommandHandler
```php
<?php

declare(strict_types=1);

use Common\Component\Persistence\PersistenceManagerInterface;

final readonly class InitCommandHandler
{
    public function __construct(
        private PaymentRepositoryInterface $payments,
        private PersistenceManagerInterface $persistenceManager,
    ) {}

    public function __invoke(InitCommand $c): void
    {
        $payment = new PaymentModel(
            user: $c->user,
            type: PaymentTypeEnum::user_top_up,
            amount: $c->amount,
        );

        $this->payments->save($payment);

        $this->persistenceManager->flush(); // транзакционная граница
    }
}
```

## Чек-лист для ревью

- [ ] Интерфейс лежит в `Domain` и зависит только от доменных типов.
- [ ] Реализация лежит в `Infrastructure`.
- [ ] Методы по критериям используют интерфейсы Criteria; нет именованных «findByXxxAndYyy».
- [ ] `getByCriteria()` возвращает массив (возможен пустой), `getOneByCriteria()` — `?Entity`.
- [ ] Исключения ORM маппятся в доменные интерфейсы исключений.
- [ ] Пагинация/сортировка — через Criteria (Limit/Offset/Sortable).
- [ ] Принимаемые и возвращаемые типы максимально конкретны; для массивов оформлен PHPDoc.

## In-memory реализация для тестов

Для unit-тестов и сценариев, где не требуется персистентность, используется in-memory реализация репозитория.
Данные хранятся в PHP-массиве внутри объекта, что обеспечивает высокую скорость и изоляцию от БД.

### Пример In-memory репозитория

```php
<?php

declare(strict_types=1);

namespace Common\Module\Health\Infrastructure\Repository\ServiceStatus;

use Common\Exception\NotFoundException;
use Common\Module\Health\Domain\Entity\ServiceStatusModel;
use Common\Module\Health\Domain\Repository\ServiceStatus\Criteria\ServiceStatusFindCriteria;
use Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusCriteriaInterface;
use Common\Module\Health\Domain\Repository\ServiceStatus\ServiceStatusRepositoryInterface;
use Override;
use Symfony\Component\Uid\Uuid;

/**
 * In-memory реализация репозитория статусов сервисов.
 * Используется для тестов и сценариев без требования персистентности.
 */
final class InMemoryServiceStatusRepository implements ServiceStatusRepositoryInterface
{
    /** @var array<string, ServiceStatusModel> */
    private array $storage = [];

    #[Override]
    public function getById(?int $id = null, ?Uuid $uuid = null): ServiceStatusModel
    {
        if ($id !== null) {
            foreach ($this->storage as $model) {
                if ($model->getId() === $id) {
                    return $model;
                }
            }
            throw new NotFoundException(sprintf('Service status with ID "%d" not found.', $id));
        }

        if ($uuid !== null) {
            foreach ($this->storage as $model) {
                if ($model->getUuid()->equals($uuid)) {
                    return $model;
                }
            }
            throw new NotFoundException(sprintf('Service status with UUID "%s" not found.', $uuid->toString()));
        }

        throw new NotFoundException('Service status not found: no ID or UUID provided.');
    }

    #[Override]
    public function getOneByCriteria(ServiceStatusCriteriaInterface $criteria): ?ServiceStatusModel
    {
        $results = $this->getByCriteria($criteria);
        return $results[0] ?? null;
    }

    #[Override]
    public function getByCriteria(ServiceStatusCriteriaInterface $criteria): array
    {
        $results = [];
        foreach ($this->storage as $model) {
            if ($this->matchesCriteria($model, $criteria)) {
                $results[] = $model;
            }
        }
        return $results;
    }

    #[Override]
    public function getCountByCriteria(ServiceStatusCriteriaInterface $criteria): int
    {
        return count($this->getByCriteria($criteria));
    }

    #[Override]
    public function exists(ServiceStatusCriteriaInterface $criteria): bool
    {
        return $this->getCountByCriteria($criteria) > 0;
    }

    #[Override]
    public function save(ServiceStatusModel $serviceStatus): void
    {
        $name = $serviceStatus->getName();
        $this->storage[$name] = $serviceStatus;
    }

    #[Override]
    public function delete(ServiceStatusModel $serviceStatus): void
    {
        $name = $serviceStatus->getName();
        unset($this->storage[$name]);
    }

    private function matchesCriteria(ServiceStatusModel $model, ServiceStatusCriteriaInterface $criteria): bool
    {
        if (!($criteria instanceof ServiceStatusFindCriteria)) {
            return true;
        }

        $name = $criteria->getName();
        if ($name !== null && $model->getName() !== $name) {
            return false;
        }

        $category = $criteria->getCategory();
        if ($category !== null && $model->getCategory() !== $category) {
            return false;
        }

        $status = $criteria->getStatus();
        if ($status !== null && $model->getStatus() !== $status) {
            return false;
        }

        return true;
    }
}
```

### Особенности In-memory реализации

1. **Временное хранение** — данные существуют только во время жизни процесса PHP.
2. **Быстродействие** — нет сетевых запросов к БД, всё в памяти.
3. **Идеально для тестов** — изоляция от БД, детерминированные результаты.
4. **Ключ хранилища** — выбирается на основе бизнес-логики (например, уникальное имя сервиса).

Полный пример: [`InMemoryServiceStatusRepository.php`](../../../src/Module/Health/Infrastructure/Repository/ServiceStatus/InMemoryServiceStatusRepository.php)
