# Компонент (Component)

**Компонент (Component)** — класс, реализующий инфраструктурные операции, не относящиеся к бизнес-логике (внешние
API/SDK, файловые хранилища, кэш, логирование). Компонент должен быть переносимым в отдельный пакет без правок кода.

При написании кода нужно учитывать: [Правила работы с внешними сервисами](external-service.md)

## Общие правила

- Независим от других слоёв; использует только зависимости из Composer.
- ❗ Все вызовы компонентов выполняются **только вне транзакций БД**.
- Обязателен интерфейс: `*ComponentInterface`; реализации: `*Component` с постфиксом реализации по таблице ниже.
- Вход/выход строго типизируем: **скаляры** и/или **DTO**. Все DTO/мапперы/фабрики — часть namespace компонента.
- Исключения транспорта/SDK **оборачиваем** в инфраструктурные исключения компонента.
- Внедрение — только через DI (constructor injection); потребители не создают компонент через `new`.
- Допускается реализация стандартных интерфейсов (PSR/Symfony Contracts) без их переопределения.
- Допускаются несколько реализаций одного интерфейса (Prod/Sandbox/Dummy).
- По умолчанию в локальной разработке интерфейс может быть замаплен на Dummy/Sandbox реализацию, в prod — на prod-реализацию (см. таблицу в разделе "Правила применения Postfix для именования реализации интерфейса").

❗ Запрещается хардкодить ключи и секреты в коде компонентов. Конфигурация всегда передаётся через контейнер и параметры из .env файлов.
ENV-переменные определяются в локальных файлах .env.local, которые добавлены в .gitignore.

❗ Не логировать секреты/токены; замаскировать (******) чувствительные поля.

Пример конфигурации:

```yaml
# services.yaml
parameters:
  module.billing.t_business.terminal_key: '%env(T_BUSINESS_PAYMENTS_TERMINAL_KEY)%'
  module.billing.t_business.secret_key: '%env(T_BUSINESS_PAYMENTS_SECRET_KEY)%'
  module.billing.t_business.base_url: '%env(T_BUSINESS_PAYMENTS_BASE_URL)%'
  module.billing.t_business.timeout: '%env(float:T_BUSINESS_PAYMENTS_TIMEOUT)%'
  module.billing.t_business.max_duration: '%env(float:T_BUSINESS_PAYMENTS_MAX_DURATION)%'
# ...
services:
# ...
  Common\Module\Billing\Integration\Component\TBusiness\TBusinessPaymentsComponent:
    arguments:
      $baseUrl: '%module.billing.t_business.base_url%'
      $terminalKey: '%module.billing.t_business.terminal_key%'
      $secretKey: '%module.billing.t_business.secret_key%'
      $timeout: '%module.billing.t_business.timeout%'
      $maxDuration: '%module.billing.t_business.max_duration%'
# ...
when@prod:
    services:
        Common\Module\Billing\Integration\Component\TBusiness\TBusinessPaymentsComponentInterface:
            alias: Common\Module\Billing\Integration\Component\TBusiness\TBusinessPaymentsComponent

when@dev:
    services:
        Common\Module\Billing\Integration\Component\TBusiness\TBusinessPaymentsComponentInterface:
            alias: Common\Module\Billing\Integration\Component\TBusiness\TBusinessPaymentsSandboxComponent

```

## Зависимости

- Компонент не обращается к окружению приложения.
- Разрешены: PSR/Symfony-контракты, внешние SDK, другие компоненты.
- Запрещены: зависимости на доменные/приложенческие классы проекта.

## Расположение

- **Integration**  
  `Common\Module\{ModuleName}\Integration\Component\{ComponentName}` - Компоненты интеграций с внешними сервисами

- **Infrastructure**  
  `Common\Module\{ModuleName}\Infrastructure\Component\{ComponentName}` - Компоненты, работающие с локальными ресурсами (ФС, кэш, процесс)

- **Presentation**  
  `{Web|Api|Console}\Component\{ComponentName}`

- **Common**  
  `Common\Component\{ComponentName}`


## Правила применения Postfix для именования реализации интерфейса

> Формат имени реализации: **`{BaseName}{Postfix}Component`**  
> Интерфейс: **`{BaseName}ComponentInterface`**  
> Где `{BaseName}` — осмысленное имя компонента (например, `TBusinessPayments`).

| Тип реализации | Postfix         | Назначение                                                                | Где использовать             | Особенности/правила                                   | Пример имени                        |
|----------------|-----------------|---------------------------------------------------------------------------|------------------------------|-------------------------------------------------------|-------------------------------------|
| **Prod**       | *без Postfix*   | Боевая реализация интеграции с внешним сервисом                           | `prod`                       | Имя отражает провайдера/транспорт                     | `TBusinessPaymentsComponent`        |
| **Sandbox**    | `Sandbox`       | Работа с «песочницей» реального сервиса (те же контракты, иные ключи/URL) | `dev`, `stage`               | Не использовать в `prod`; помечайте окружение в логах | `TBusinessPaymentsSandboxComponent` |
| **Dummy**      | `Dummy`         | «Пустышка», ничего не делает, возвращает предсказуемый успех              | локальная разработка, `demo` | Исключить из `prod`                                   | `TBusinessPaymentsDummyComponent`   |
| **Fake**       | `Fake`          | Упрощённая «рабочая» логика без внешних вызовов                           | `test` (интеграц./функц.)    | Детерминированность; без I/O                          | `TBusinessPaymentsFakeComponent`    |
| **Stub**       | `Stub`          | Жёстко зашитые ответы без логики                                          | `test` (unit)                | Точечные сценарии                                     | `TBusinessPaymentsStubComponent`    |
| **Spy**        | `Spy`           | Запоминает вызовы/аргументы для проверок                                  | `test` (unit)                | Только сбор фактов                                    | `TBusinessPaymentsSpyComponent`     |

## Как используем

- Только через интерфейс и DI:
  ```php
  // ❌ Нельзя:
  // (new TBusinessPaymentsComponent(...))->init($dto);

  // ✅ Правильно: внедряем интерфейс
  use Common\Module\Billing\Domain\Service\Payment\InitPaymentResultDto;

  final readonly class InitPaymentService implements InitPaymentServiceInterface
  {
      public function __construct(
          private TBusinessPaymentsComponentInterface $component,
      ) {}

      #[Override]
      public function init(string $amount, string $description): InitPaymentResultDto
      {
          $request = new InitRequestDto($amount, $description);
          $response = $this->component->init($request);

          return new InitPaymentResultDto(
              success: $response->success,
              paymentUrl: $response->paymentUrl,
          );
      }
  }
  ```

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\Billing\Integration\Component\TBusiness;

use Common\Exception\InfrastructureException;
use Common\Module\Billing\Integration\Component\TBusiness\Dto\InitRequestDto;
use Common\Module\Billing\Integration\Component\TBusiness\Dto\InitResponseDto;
use Common\Module\Billing\Integration\Component\TBusiness\Mapper\InitRequestMapper;
use Common\Module\Billing\Integration\Component\TBusiness\Mapper\InitResponseMapper;
use Override;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

interface TBusinessPaymentsComponentInterface
{
    public function init(InitRequestDto $dto): InitResponseDto;
}

final readonly class TBusinessPaymentsComponent implements TBusinessPaymentsComponentInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
        private string $terminalKey,
        private string $secretKey,
        private InitRequestMapper $initRequestMapper,
        private InitResponseMapper $initResponseMapper,
    ) {}

    /**
     * @see https://www.tbank.ru/kassa/dev/payments/#tag/Standartnyj-platezh/operation/Init
     */
    #[Override]
    public function init(InitRequestDto $dto): InitResponseDto
    {
        $payload = $this->initRequestMapper->map($dto, $this->terminalKey, $this->secretKey);

        try {
            $response = $this->httpClient->request('POST', rtrim($this->baseUrl, '/') . '/Init', [
                'json' => $payload,
            ]);
            $data = $response->toArray();
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            throw new InfrastructureException($e->getMessage(), previous: $e);
        }

        return $this->initResponseMapper->map($data);
    }
}
```

## Чек-лист код ревью

- [ ] Публичный API интерфейса строго типизирован (скаляры/DTO).
- [ ] Нет зависимостей на другие слои.
- [ ] Нет захардкоженных секретов.
- [ ] Таймауты заданы через конфигурацию.
- [ ] Логируются: старт, успех, неуспех, завершение; чувствительные поля замаскированы.
- [ ] Логи содержат контекст (`url`, `params`).
- [ ] Исключения транспорта/SDK обёрнуты в инфраструктурные.
- [ ] Нет внешних вызовов внутри транзакций БД.
- [ ] Выбрана корректная реализация по таблице постфиксов и явно зафиксирована в DI по окружениям.
