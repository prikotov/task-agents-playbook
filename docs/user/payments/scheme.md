# Схемы работы с платежами (T-Business)

В системе реализовано три основных сценария проведения платежей. Ниже представлены диаграммы для каждого из них.

## 1. Разовое пополнение счета (One-time Payment)

Пользователь просто пополняет баланс на произвольную сумму.

```mermaid
sequenceDiagram
    autonumber
    actor User
    participant Controller as Web Controller
    participant Command as InitOneTimeCommandHandler
    participant Component as TBusinessPaymentsComponent
    participant TBank as T-Bank API
    participant DB as Database

    User->>Controller: Запрос на пополнение (Top-up)
    Controller->>Command: Dispatch InitOneTimeCommand
    Command->>DB: Создание PaymentModel (Status: New)
    Command->>Component: init(InitRequestDto)
    Component->>TBank: POST /v2/Init
    TBank-->>Component: JSON {PaymentURL, PaymentId}
    Component-->>Command: InitResponseDto
    Command->>DB: Обновление TBusinessPaymentModel (PaymentId)
    Command-->>Controller: Redirect URL
    Controller-->>User: Редирект на форму оплаты T-Bank

    User->>TBank: Оплата
    TBank-->>User: Успех/Ошибка
    
    TBank->>Controller: Webhook (Notification)
    Controller->>Command: NotificationCommand
    Command->>DB: Обновление статуса платежа
    Command->>DB: Зачисление средств на баланс (UserBalance)
```

## 2. Подключение тарифа с привязкой карты (Plan Subscription)

Пользователь выбирает тариф и оплачивает его. При этом происходит привязка карты (сохранение `RebillId`) для будущих списаний.

```mermaid
sequenceDiagram
    autonumber
    actor User
    participant Controller as Web Controller
    participant Command as InitRecurrentCommandHandler
    participant Component as TBusinessPaymentsComponent
    participant TBank as T-Bank API
    participant DB as Database

    User->>Controller: Подключение тарифа (Subscribe)
    Controller->>Command: Dispatch InitRecurrentCommand
    Command->>DB: Создание PaymentModel (Linked to Plan)
    Command->>Component: init(InitRequestDto, Recurrent=Y)
    Component->>TBank: POST /v2/Init (Recurrent=Y)
    TBank-->>Component: JSON {PaymentURL, PaymentId}
    Command-->>Controller: Redirect URL
    Controller-->>User: Редирект на форму оплаты

    User->>TBank: Оплата и привязка карты
    
    TBank->>Controller: Webhook (Notification)
    Controller->>Command: NotificationCommand
    Command->>DB: Обновление статуса
    Command->>DB: Сохранение RebillId в TBusinessPaymentModel
    Command->>DB: Активация тарифа (UserBilling)
```

## 3. Автоматическое продление тарифа (Autopay)

Системный процесс (Cron) проверяет необходимость продления тарифа и списывает средства, используя сохраненный `RebillId`.

```mermaid
sequenceDiagram
    autonumber
    participant Cron as System (Cron)
    participant Command as ProcessAutopayCommandHandler
    participant Service as ChargePaymentService
    participant Component as TBusinessPaymentsComponent
    participant TBank as T-Bank API
    participant DB as Database

    Cron->>Command: Dispatch ProcessAutopayCommand
    Command->>DB: Поиск пользователей для списания
    Command->>DB: Проверка баланса и условий тарифа
    Command->>DB: Поиск RebillId от предыдущего платежа
    
    Command->>DB: Создание PaymentModel (Status: Initialized)
    Command->>Service: charge(Payment, RebillId)
    Service->>Component: charge(ChargeRequestDto)
    Component->>TBank: POST /v2/Charge (Token/RebillId)
    TBank-->>Component: JSON {Success, Status, ...}
    
    Service-->>Command: ChargeResult
    Command->>DB: Обновление PaymentModel (Status: Processing/Rejected)
    
    opt Если успешно
        Command->>DB: Продление подписки (UserBilling)
    end

    Note right of TBank: Банк также может прислать Webhook для подтверждения финального статуса
```

## Сводная таблица команд

| Сценарий | Command Class | T-Bank Method | Особенности |
|---|---|---|---|
| **Пополнение** | `InitOneTimeCommand` | `/v2/Init` | Обычный платеж, увеличивает баланс. |
| **Подписка** | `InitRecurrentCommand` | `/v2/Init` | Передается флаг рекуррентности. Сохраняется `RebillId`. |
| **Авто-списание** | `ProcessAutopayCommand` | `/v2/Charge` | Использует `RebillId`. Без участия пользователя. |
