# Исключение (Exception)

**Исключение (Exception)** — часть интерфейса класса, модуля и архитектурного слоя. Оно сигнализирует о невозможности
продолжения работы и позволяет отделить корректные сценарии выполнения от ошибочных.

## Общие правила

* Исключение **является частью публичного интерфейса** класса, модуля и слоя.
* При оборачивании одного исключения в другое всегда передавать `previous`. Это сохраняет полный trace для логов.
* ❗ Классы одного слоя **не могут выбрасывать исключения других слоёв или библиотек**. Их нужно изолировать и
  оборачивать.
* Исключение кидается, если продолжение нормальной работы невозможно.
* По умолчанию тексты исключений не предназначены для показа пользователю (только для логирования). Исключение делается
  только для ошибок валидации.
* При перехвате:
    * *выбрасываем реализацию, ловим интерфейс* — в `catch` указываем интерфейсы (`DomainExceptionInterface`,
      `ValidationExceptionInterface` и т.д.).
    * в `@throws` phpDoc также указываем интерфейсы.
* Не перехватываем базовые PHP-исключения (\Throwable, \RuntimeException, \InvalidArgumentException).

## Категории

### Ошибки по вине клиента (Client Errors)

* Преобразуются в **4xx HTTP Codes** на слое Presentation.
* Используются в слоях: Application, Domain.

#### ValidationException

Клиент передал некорректные данные.
*Пример*: не указан получатель при отправке email.

#### NotFoundException

Клиент запросил несуществующие данные.
*Пример*: запрос статуса по несуществующему идентификатору.

#### ConflictException
Конфликт состояния ресурса.
*Пример*: пользователь с таким email уже существует.

#### AccessDeniedException
Ошибка доступа в случае если ограничение продиктовано бизнес-правилами.
*Пример*: доступ к объекту запрещён текущему пользователю.

### Ошибки выполнения (Runtime Errors)

* Преобразуются в **5xx HTTP Codes**.
* Фреймворк по умолчанию выдаёт `500 Internal Server Error`.
* Ловить в контроллерах имеет смысл, если нужно уточнить сообщение (например, *Gateway Error*).

#### InfrastructureException

Ошибки окружения или внешних сервисов.
*Примеры*: Redis недоступен, интеграционный API вернул неожиданный ответ.

#### ConfigurationException

Неверные или отсутствующие параметры в конфигурации.

#### DomainException

Неожиданный бизнес-кейс.
*Пример*: попытка заправить бензином электромобиль.

## Зависимости

* Исключения сторонних библиотек перехватываются **сразу**.
* Далее они либо обрабатываются, либо маппятся в свои исключения.
* Новое исключение должно содержать предыдущее (`previous`).

## Расположение

* Общие исключения:

  ```
  Common\Exception\{Category}\{ExceptionName}Exception
  ```
* Частные исключения располагаются в своём модуле, в соответствующем слое:

  ```
  Common\Module\{ModuleName}\Domain\Exception\{ExceptionName}Exception
  ```
* Название исключения должно явно отражать причину ошибки.

## Как используем

* В каждом слое:
    * внешние исключения перехватываются и заменяются на свои;
    * наружу выбрасываются только исключения слоя или общие (`Common\Exception`).
* В `catch` блоках используем интерфейсы.
* На Presentation-слое исключения маппятся в HTTP-ошибки (4xx/5xx).

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\User\Domain\Exception;

use Common\Exception\ValidationExceptionInterface;

final class WrongPhoneFormatException extends \InvalidArgumentException implements ValidationExceptionInterface
{
    public function __construct(string $phone, \Throwable $previous = null)
    {
        parent::__construct(sprintf('Wrong phone format: %s', $phone), 0, $previous);
    }
}
```

Перехват:

```php
try {
    $handler->handle($dto);
} catch (ValidationExceptionInterface $exception) {
    throw new BadRequestHttpException($exception->getMessage(), $exception);
} catch (InfrastructureExceptionInterface $exception) {
    throw new ServerErrorHttpException('Gateway Error', $exception);
}
```
