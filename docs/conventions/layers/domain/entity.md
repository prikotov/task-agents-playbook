# Сущности (Entity)

**Сущность (Entity)** — объект доменной модели, обладающий идентичностью и набором атрибутов/инвариантов.

Сущность ключевое понятие DDD: она отражает **бизнес-понятие** в рамках ограниченного контекста и хранит знания о своих
бизнес-процессах.  
❗ Важно: не путать с «моделью» из MVC — та лишь представляет данные, а сущность DDD концентрирует бизнес-логику.

В проекте применяется подход **persistence-oriented domain**: сущности реализуются в виде ORM-моделей Doctrine с
атрибутами и сохраняются через репозитории. Мы используем **постфикс `Model`** (например, `PaymentModel`) вместо
`Entity`, чтобы подчеркнуть связь с ORM.

## Общие правила

- Все сущности располагаются в `Domain\Entity\*`.
- Для ORM-сущностей используется постфикс `Model` (например, `PaymentModel`, `TBusinessPaymentModel`).
- Сущность должна иметь **идентификатор** (int и/или uuid) и поддерживать неизменяемую идентичность.
- Название таблицы должно быть в **единственном числе** и отражать **зону ответственности** сущности.
- Индексы именуются по паттерну `{тип}_{таблица}__{колонки}`, где тип: `i` (INDEX), `ui` (UNIQUE).
- Поля жизненного цикла (`status`, `state`, любые индикаторы процессов) описываются через int-backed enum и мапятся на SMALLINT-колонки Doctrine для единообразия и быстрых индексов. Примеры: `NotificationModel::$status`, `PaymentModel::$status`.
- Внутри сущности допустимы атрибуты Doctrine и технические трейты (`IdTrait`, `UuidTrait`, `InsTsTrait` и др.).
- Сущности содержат только бизнес-логику и инварианты.
- Сущности не сохраняют себя сами, сохранение выполняет репозиторий, транзакцию контролирует CommandHandler.
- Конфигурацию регистрации сущностей в Doctrine смотрите в [Конфигурировании модулей](../../modules/configuration.md).
- Инварианты проверяются в конструкторе или сеттерах, чтобы невозможно было создать или перевести сущность в
  некорректное состояние.
- Сущность должна избегать анемичности. Простейшие бизнес-правила концентрируются в сущности, сложные процессы —
  выносятся в сервисы.
- Для сложной логики создания сущности используется **фабрика** (`CreateFactory`), статические методы `create()` в сущностях запрещены.

> Инвариант — это правило или ограничение бизнес-домена, которое должно оставаться истинным для сущности на протяжении
> всего её жизненного цикла. Инварианты проверяются при создании и изменении сущности и гарантируют, что она не может
> существовать или перейти в некорректное состояние.

> Анемичная сущность — это сущность, которая содержит только поля данных и геттеры/сеттеры, но не содержит собственной
> бизнес-логики. Такая сущность не отражает бизнес-процессы и правила, а превращается в простую DTO и перестаёт
> выполнять роль полноценной сущности домена.

## Отличие от Value Object (VO)

- **Сущность (Entity/Model)**: имеет уникальный идентификатор, может изменять своё состояние через методы.
- **Value Object (VO)**: не имеет идентичности, определяется только значениями и всегда иммутабелен (неизменяем после
  создания).

> Имутабельность упрощает понимание и предсказуемость кода, делает объекты безопасными для многопоточного доступа и
> помогает явно выражать неизменность бизнес-значений. Любая операция, которая «изменяет» объект, на самом деле должна
> создавать новый экземпляр с обновлённым состоянием.

## Расположение

- В слое [Domain](../domain.md):

```php
Common\Module\{ModuleName}\Domain\Entity\{Context}\{EntityName}Model
```

## Как используем

- В Application-слое:
    - Через внедрение интерфейсов репозиториев (`DI`).
    - Хендлеры команд и use-case’ы создают новые сущности и вызывают `save()` в репозитории.
    - После всех операций вызывается `flush()` в хендлере.
- В Domain-слое:
    - Сущности содержат бизнес-инварианты и методы изменения состояния.
    - Для простых значений используем VO/DTO.
    - Создание может делегироваться фабрикам.

## Пример

### Сущность

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Domain\Entity;

use Common\Component\Clock\ClockFactory;
use Common\Component\Doctrine\Model\IdModelInterface;
use Common\Component\Doctrine\Model\InsTsModelInterface;
use Common\Component\Doctrine\Model\UuidModelInterface;
use Common\Exception\DomainException;
use Common\Module\Billing\Domain\Enum\PaymentStatusEnum;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;

// Таблица: единственное число, отражает контекст (billing) и сущность (payment)
#[ORM\Entity]
#[ORM\Table(name: 'billing_payment')]
// Индексы: паттерн {тип}_{таблица}__{колонки}, где i=INDEX, ui=UNIQUE
#[ORM\Index(name: 'i_billing_payment__user_id', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'ui_billing_payment__uuid', columns: ['uuid'])]
class PaymentModel implements IdModelInterface, UuidModelInterface, InsTsModelInterface
{
    // Технические трейты: id, uuid и время создания
    use IdTrait;
    use UuidTrait;
    use InsTsTrait;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: UserModel::class)]
        private UserModel $user,
        #[ORM\Column(type: Types::STRING, length: 32, enumType: PaymentTypeEnum::class)]
        private PaymentTypeEnum $type,
        #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 6)]
        private string $amount,
        #[ORM\Column(type: Types::SMALLINT, enumType: PaymentStatusEnum::class)]
        private PaymentStatusEnum $status = PaymentStatusEnum::initialized,
    ) {
        // Инвариант: сумма платежа должна быть положительной
        if ($amount <= 0) {
            throw new DomainException('Payment amount must be positive');
        }

        $this->uuid = Uuid::v7();
        $this->insTs = ClockFactory::create()->now();
    }

    public function setStatus(PaymentStatusEnum $status): void
    {
        // Инвариант: нельзя переводить completed → initialized
        if ($this->status === PaymentStatusEnum::completed && $status === PaymentStatusEnum::initialized) {
            throw new DomainException('Cannot revert completed payment to initialized');
        }
        $this->status = $status;
    }
}
```

## Чек-лист для ревью

- [ ] Сущность расположена в `Domain\Entity\*` и имеет постфикс `Model`.
- [ ] Внутри сущности простая бизнес-логика, инварианты и атрибуты Doctrine; сложная логика вынесена в сервисы.
- [ ] Проверяются инварианты в конструкторе/сеттере.
- [ ] Таблица названа в единственном числе и отражает зону ответственности (например, `customer`, `billing_payment`).
- [ ] Индексы именованы по паттерну `{тип}_{таблица}__{колонки}` (например, `i_customer__source`, `ui_customer__status__choice_status`).
- [ ] Для сложной логики создания сущности используется фабрика (`CreateFactory`), статический метод `create()` запрещён.
