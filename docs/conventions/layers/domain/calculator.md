# Калькуляторы (Calculator)

**Калькулятор (Calculator)** — класс, отвечающий за выполнение математических и временных вычислений.

Калькулятор инкапсулирует сложные расчёты, которые не относятся напрямую к состоянию сущности, но являются частью доменной логики. Он принимает входные данные и возвращает результат, не изменяя состояние системы.

## Общие правила

- В названии класса обязательно указывается постфикс `Calculator`.
- Методы калькулятора именуем calculate*(); один public-метод = один расчёт (SRP).
- Входы и выходы: примитивы, VO/DTO/Entity своего модуля. Массивы допускаются только как list<T>/shape-типы.
- Время: `DateVO` / `DateTimeImmutable`.
- `now` не создаём внутри, источник времени только `Psr\Clock\ClockInterface`.
- Unit-тесты обязательны.
- Композиция разрешена: может использовать другие калькуляторы.
- Без состояния (Stateless). Static-свойства запрещены.
- Считает результат (формулы/правила расчёта).
- Не валидирует бизнес-ограничения (инварианты — в Entity/VO).

## Зависимости

**Разрешено**:
    - Внедрение других калькуляторов через конструктор.
    - Использование VO, DTO, сущностей.
    - Использование стандартных типов PHP и date-time утилит.

**Запрещено**:
    - Любое взаимодействие с БД.
    - Внешние HTTP-клиенты, SDK, API-вызовы, I/O.
    - Зависимости на фреймворк.
    - Репозитории, message bus, event dispatcher, логгеры.
    - Глобальные источники времени.

## Расположение

```php
Common\Module\{ModuleName}\Domain\Calculator\{GroupName}\{Name}Calculator
```

{GroupName} не обязателен. Если в модуле >1 калькулятора по одной теме можно использовать папку группы.

## Как используем

- Внедряем через конструктор.
- Используем в Domain и Application
- Сущности и доменные сервисы используют калькуляторы для выполнения сложных вычислений.
- Use case'ы и хендлеры команд могут использовать калькуляторы для подготовки данных перед использованием.


## Пример

### Общий калькулятор косинусного сходства

```php
<?php
namespace Common\Module\Rag\Domain\Calculator\Vector;

declare(strict_types=1);

use InvalidArgumentException;

/**
 * @link https://en.wikipedia.org/wiki/Cosine_similarity
 */
final class CosineSimilarityCalculator
{
    /**
     * @param list<float> $vectorA
     * @param list<float> $vectorB
     * @return float
     */
    public function calculate(array $vectorA, array $vectorB): float
    {
        if (count($vectorA) !== count($vectorB)) {
            throw new InvalidArgumentException("Vector dimensions must match.");
        }
        if ($n === 0) {
            throw new InvalidArgumentException('Vectors must not be empty.');
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $normA += $vectorA[$i] * $vectorA[$i];
            $normB += $vectorB[$i] * $vectorB[$i];
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
```

### Бизнес-калькулятор скидки по промокоду

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Domain\Calculator;

use Common\Module\Billing\Domain\Entity\PromoCodeModel;
use Common\Module\Billing\Domain\Enum\PromoCodeDiscountTypeEnum;

final readonly class PromoCodeDiscountCalculator
{
    private const int SCALE = 6;

    /**
     * @param numeric-string $amount
     * @return numeric-string
     */
    public function calculate(PromoCodeModel $promoCode, string $amount): string
    {
        $discount = match ($promoCode->getDiscountType()) {
            PromoCodeDiscountTypeEnum::percentage => $this->calculatePercentage($promoCode->getDiscountValue(), $amount),
            PromoCodeDiscountTypeEnum::fixedAmount => $promoCode->getDiscountValue(),
        };

        if (bccomp($discount, '0', self::SCALE) < 0) {
            $discount = '0';
        }

        if (bccomp($discount, $amount, self::SCALE) === 1) {
            return bcadd($amount, '0', self::SCALE);
        }

        return bcadd($discount, '0', self::SCALE);
    }

    /**
     * @param numeric-string $percentage
     * @param numeric-string $amount
     *
     * @return numeric-string
     */
    private function calculatePercentage(string $percentage, string $amount): string
    {
        $product = bcmul($amount, $percentage, self::SCALE + 2);

        return bcdiv($product, '100', self::SCALE);
    }
}
```

## Чек-лист для ревью

- [ ] Расположен в `Domain\Calculator\*` и имеет постфикс `Calculator`.
- [ ] Класс является `readonly`, не имеет внутреннего состояния.
- [ ] Не содержит изменений состояния БД и работы с фреймворком.
- [ ] Входные и выходные данные типизированы.
- [ ] При работе с датами используются `DateVO` или `DateTimeImmutable`
- [ ] now только через Psr\Clock\ClockInterface.
- [ ] Покрыт unit-тестами.
