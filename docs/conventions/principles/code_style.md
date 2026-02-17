# Стиль кода (Code Style)

Документ описывает стандарты оформления кода в проекте TasK. Следование этим правилам обеспечивает единообразие кода, улучшает читаемость и упрощает поддержку.

## Стандарты оформления кода

### PSR-12 как базовый стандарт

Проект следует стандарту [PSR-12: Extended Coding Style](https://www.php-fig.org/psr/psr-12/) — это базовое руководство по оформлению PHP-кода.

**Ключевые требования PSR-12:**

- 4 пробела для отступов (не табы)
- Открывающая фигурная скобка на новой строке для классов и методов
- Строгая типизация (`declare(strict_types=1);`)
- Использование `use` для импорта классов
- Правильное форматирование массивов и аргументов функций

### Длина строки

- **Максимум 120 символов** для строки кода
- Допускается превышение для длинных URL, SQL-запросов и строковых литералов
- Используйте разрыв строки для улучшения читаемости длинных выражений

**Пример:**

```php
// ❌ Плохо: слишком длинная строка
$payment = $this->paymentService->createPayment($user, $amount, $currency, $description, $metadata);

// ✅ Хорошо: разрыв строки для читаемости
$payment = $this->paymentService->createPayment(
    user: $user,
    amount: $amount,
    currency: $currency,
    description: $description,
    metadata: $metadata,
);
```

### Отступы

- **4 пробела** для отступов (не табы)
- Не смешивайте пробелы и табы
- Используйте автоформатирование IDE для соблюдения стандарта

### Форматирование фигурных скобок

**Классы и методы:**

```php
// ✅ Правильно: открывающая скобка на новой строке
final readonly class PaymentService
{
    public function createPayment(
        User $user,
        string $amount,
        CurrencyEnum $currency,
    ): PaymentModel {
        // ...
    }
}
```

**Управляющие конструкции:**

```php
// ✅ Правильно: открывающая скобка на той же строке
if ($condition) {
    // ...
} elseif ($otherCondition) {
    // ...
} else {
    // ...
}

foreach ($items as $item) {
    // ...
}
```

### Пустые строки между секциями

- **Одна пустая строка** между методами класса
- **Одна пустая строка** между логическими секциями внутри метода
- **Две пустые строки** между классами в одном файле (если допустимо)

**Пример:**

```php
final readonly class PaymentService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function createPayment(
        User $user,
        string $amount,
        CurrencyEnum $currency,
    ): PaymentModel {
        $payment = new PaymentModel(
            user: $user,
            amount: $amount,
            currency: $currency,
        );

        $this->paymentRepository->save($payment);
        $this->eventBus->dispatch(new PaymentCreatedEvent($payment->getUuid()));

        return $payment;
    }

    public function cancelPayment(Uuid $paymentUuid): void
    {
        $payment = $this->paymentRepository->getByUuid($paymentUuid);
        $payment->setStatus(PaymentStatusEnum::cancelled);
        $this->paymentRepository->save($payment);
    }
}
```

## Правила именования

### Классы

- **PascalCase** (первая буква заглавная, каждое слово с заглавной)
- Описательные имена, отражающие назначение
- Постфиксы для специальных типов: `Model` (сущности), `Dto`, `Enum`, `Interface`, `Service`, `Repository`, `Handler`

**Примеры:**

```php
// ✅ Правильно
PaymentModel
PaymentService
PaymentRepositoryInterface
PaymentDto
PaymentStatusEnum

// ❌ Плохо
payment
payment_service
Payment
paymentDTO
```

### Методы

- **camelCase** (первая буква строчная, каждое следующее слово с заглавной)
- Начинаются с глагола, описывающего действие
- Используйте префиксы для группировки: `get`, `set`, `is`, `has`, `create`, `update`, `delete`

**Примеры:**

```php
// ✅ Правильно
public function createPayment(): PaymentModel
public function getPaymentByUuid(Uuid $uuid): ?PaymentModel
public function isPaymentCompleted(): bool
public function hasActiveSubscription(): bool

// ❌ Плохо
public function Payment(): PaymentModel
public function payment_by_uuid(Uuid $uuid): ?PaymentModel
public function completed(): bool
```

### Переменные

- **camelCase**
- Описательные имена, отражающие содержание
- Избегайте сокращений (исключение: общепринятые)

**Примеры:**

```php
// ✅ Правильно
$paymentAmount
$userEmail
$isActive
$totalPrice

// ❌ Плохо
$amt
$em
$flag
$tp
```

### Константы

- **UPPER_SNAKE_CASE** (все буквы заглавные, слова разделены подчёркиванием)
- Константы класса: объявляются с модификатором `public const` или `private const`
- Глобальные константы: избегайте, используйте классы-константы

**Примеры:**

```php
// ✅ Правильно
final readonly class PaymentStatusEnum extends Enum
{
    public const INITIALIZED = 'initialized';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';
}

// ❌ Плохо
final readonly class PaymentStatusEnum extends Enum
{
    public const initialized = 'initialized';
    public const Completed = 'completed';
}
```

### Интерфейсы

- **Суффикс `Interface`** (например: `PaymentRepositoryInterface`)
- Не используйте префикс `I` (например: `IPaymentRepository`)

**Примеры:**

```php
// ✅ Правильно
interface PaymentRepositoryInterface
{
    public function save(PaymentModel $payment): void;
    public function getByUuid(Uuid $uuid): ?PaymentModel;
}

// ❌ Плохо
interface IPaymentRepository
{
    public function save(PaymentModel $payment): void;
}
```

## Комментарии и документация

### PHPDoc для всех публичных классов, интерфейсов и методов

PHPDoc обязателен для:
- Публичных классов
- Интерфейсов
- Публичных и защищённых методов
- Свойств классов (если неочевидно)

**Структура PHPDoc:**

```php
/**
 * Сервис для управления платежами.
 *
 * @see PaymentModel
 */
final readonly class PaymentService
{
    /**
     * Создаёт новый платёж.
     *
     * @throws DomainException если сумма платежа отрицательная
     * @throws ConflictException если платёж с таким UUID уже существует
     */
    public function createPayment(
        User $user,
        string $amount,
        CurrencyEnum $currency,
    ): PaymentModel {
        // ...
    }
}
```

### Комментарии к сложной логике

Комментируйте код, который:
- Содержит нетривиальные алгоритмы
- Использует неочевидные обходные пути
- Требует контекста для понимания

**Пример:**

```php
// Используем кэш на 5 минут, чтобы избежать частых запросов к внешнему API
// External API имеет ограничение 100 запросов в минуту
$cachedRate = $this->cache->get('fx_rate_' . $currency, ttl: 300);
if ($cachedRate === null) {
    $cachedRate = $this->externalApi->getRate($currency);
    $this->cache->set('fx_rate_' . $currency, $cachedRate, ttl: 300);
}
```

### Запрет на избыточные комментарии

**Очевидный код не комментируйте:**

```php
// ❌ Плохо: избыточный комментарий
// Проверяем, если пользователь авторизован
if ($user->isAuthenticated()) {
    // Перенаправляем на главную страницу
    return $this->redirectToRoute('home');
}

// ✅ Хорошо: код самодокументируемый
if ($user->isAuthenticated()) {
    return $this->redirectToRoute('home');
}
```

### Примеры использования в PHPDoc

Добавляйте примеры использования для сложных методов и классов:

```php
/**
 * Создаёт платёж с автоматической конвертацией валюты.
 *
 * Пример использования:
 * ```php
 * $payment = $paymentService->createPaymentWithConversion(
 *     user: $user,
 *     amount: '100.00',
 *     fromCurrency: CurrencyEnum::USD,
 *     toCurrency: CurrencyEnum::EUR,
 * );
 * ```
 *
 * @throws DomainException если курс конвертации недоступен
 */
public function createPaymentWithConversion(
    User $user,
    string $amount,
    CurrencyEnum $fromCurrency,
    CurrencyEnum $toCurrency,
): PaymentModel {
    // ...
}
```

## Примеры и антипаттерны

### Правильный пример кода

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Application\Service;

use Common\Exception\DomainException;
use Common\Module\Billing\Domain\Entity\PaymentModel;
use Common\Module\Billing\Domain\Enum\PaymentStatusEnum;
use Common\Module\Billing\Domain\Repository\Payment\PaymentRepositoryInterface;
use Common\Module\User\Domain\Entity\UserModel;
use Common\Module\User\Domain\Repository\User\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Сервис для управления платежами.
 *
 * Оркестрирует создание, обновление и отмену платежей.
 */
final readonly class PaymentService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * Создаёт новый платёж для пользователя.
     *
     * @throws DomainException если пользователь не найден
     * @throws DomainException если сумма платежа отрицательная
     */
    public function createPayment(
        Uuid $userUuid,
        string $amount,
        string $currency,
    ): PaymentModel {
        $user = $this->userRepository->getByUuid($userUuid);
        $payment = new PaymentModel(
            user: $user,
            amount: $amount,
            currency: $currency,
            status: PaymentStatusEnum::initialized,
        );

        $this->paymentRepository->save($payment);

        return $payment;
    }

    /**
     * Отменяет платёж по UUID.
     *
     * @throws DomainException если платёж не найден
     * @throws DomainException если платёж уже отменён или завершён
     */
    public function cancelPayment(Uuid $paymentUuid): void
    {
        $payment = $this->paymentRepository->getByUuid($paymentUuid);
        $payment->cancel();
        $this->paymentRepository->save($payment);
    }
}
```

### Неправильный пример кода с объяснением

```php
<?php

// ❌ Плохо: нарушены множественные правила стиля кода

// 1. Нет declare(strict_types=1)
// 2. Отсутствует PHPDoc для класса
// 3. Имена переменных не описательные
// 4. Отсутствуют типы аргументов и возвращаемого значения
// 5. Нет обработки исключений
// 6. Избыточные комментарии
// 7. Нарушено форматирование (длинные строки)

namespace Common\Module\Billing\Application\Service;

use Common\Module\Billing\Domain\Entity\PaymentModel;
use Common\Module\Billing\Domain\Repository\Payment\PaymentRepositoryInterface;

class paymentService // ❌ Имя класса не в PascalCase
{
    private $repo; // ❌ Не описательное имя, нет типа

    public function __construct(PaymentRepositoryInterface $r) // ❌ Не описательный аргумент
    {
        $this->repo = $r;
    }

    // ❌ Нет PHPDoc
    // ❌ Нет типов аргументов и возвращаемого значения
    public function create($uuid, $amt, $curr) // ❌ Не описательные имена
    {
        // Получаем пользователя
        $u = $this->userRepository->getByUuid($uuid); // ❌ Не описательное имя

        // Создаём платёж
        $p = new PaymentModel($u, $amt, $curr); // ❌ Не описательное имя

        // Сохраняем
        $this->repo->save($p); // ❌ Избыточный комментарий

        return $p;
    }
}
```

### Частые ошибки и как их избегать

#### Ошибка 1: Отсутствие строгой типизации

```php
// ❌ Плохо
namespace Common\Module\Billing\Application\Service;

class PaymentService
{
    // ...
}

// ✅ Хорошо
declare(strict_types=1);

namespace Common\Module\Billing\Application\Service;

class PaymentService
{
    // ...
}
```

#### Ошибка 2: Неописательные имена переменных

```php
// ❌ Плохо
$data = $this->repository->get($id);
foreach ($data as $d) {
    echo $d['name'];
}

// ✅ Хорошо
$users = $this->userRepository->getAll();
foreach ($users as $user) {
    echo $user->getName();
}
```

#### Ошибка 3: Избыточные комментарии

```php
// ❌ Плохо
// Проверяем, если платёж существует
if ($payment !== null) {
    // Возвращаем платёж
    return $payment;
} else {
    // Возвращаем null
    return null;
}

// ✅ Хорошо
return $payment;
```

#### Ошибка 4: Нарушение длины строки

```php
// ❌ Плохо
$payment = $this->paymentService->createPayment($user, $amount, $currency, $description, $metadata, $createdAt);

// ✅ Хорошо
$payment = $this->paymentService->createPayment(
    user: $user,
    amount: $amount,
    currency: $currency,
    description: $description,
    metadata: $metadata,
    createdAt: $createdAt,
);
```

#### Ошибка 5: Отсутствие PHPDoc для публичных методов

```php
// ❌ Плохо
public function createPayment(User $user, string $amount): PaymentModel
{
    // ...
}

// ✅ Хорошо
/**
 * Создаёт новый платёж для пользователя.
 *
 * @throws DomainException если сумма платежа отрицательная
 */
public function createPayment(User $user, string $amount): PaymentModel
{
    // ...
}
```

## Чек-лист для ревью

- [ ] Файл начинается с `declare(strict_types=1);`
- [ ] Соблюдается PSR-12 (форматирование, отступы, скобки)
- [ ] Длина строки не превышает 120 символов
- [ ] Классы названы в PascalCase с описательными именами
- [ ] Методы названы в camelCase, начинаются с глагола
- [ ] Переменные названы в camelCase, описательные имена
- [ ] Константы названы в UPPER_SNAKE_CASE
- [ ] Интерфейсы имеют суффикс `Interface`
- [ ] Публичные классы, интерфейсы и методы имеют PHPDoc
- [ ] Нет избыточных комментариев для очевидного кода
- [ ] Сложная логика прокомментирована
- [ ] Используются именованные аргументы для улучшения читаемости
