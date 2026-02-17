# Module Structure: Strict Rules for AI Agents

**Все новые модули размещаются только в каталоге** `src/Module/{ModuleName}`.
**Имя модуля** должно быть уникальным и отражать бизнес-область.
**Любое отклонение от этой структуры запрещено без явной задачи.**

Каждый модуль обязан содержать четыре каталога верхнего уровня: `Domain/`, `Application/`, `Infrastructure/`, `Integration/`.
**Любое добавление, удаление или переименование слоёв — только по согласованию.**

- **Domain** (`Domain/`):
    - Содержит бизнес-логику.
    - Может содержать только: Entity, ValueObject, Enum, Specification, интерфейсы репозиториев, доменные сервисы.
    - Строго запрещено: любые зависимости на другие слои или сторонние библиотеки (кроме PHP std и своих интерфейсов).
- **Application** (`Application/`):
    - Координирует работу бизнес-логики.
    - Может содержать только: UseCases handlers, Application-сервисы, Query/Command объекты.
    - Строго запрещено содержание инфраструктурных деталей.
- **Infrastructure** (`Infrastructure/`):
    - Содержит технические реализации **внутренних** зависимостей домена.
    - Может содержать репозитории, хранилища, интеграция с БД, кэш, файловая система, логирование, мониторинг, настройка окружения.
    - Строго запрещено наличие кода интеграций с внешними системами или другими модулями.
- **Integration** (`Integration/`):
    - Предназначен для взаимодействия с внешними системами и межмодульного взаимодействия
    - Реализация паттерна «антикоррупционного слоя» для защиты бизнес-логики от изменений в интеграциях.
    - Разрешено: работа с внешними API, очередями, событиями, интеграционные сервисы.
    - Запрещено: реализация любых инфраструктурных деталей и внутренней бизнес-логики.

**Все новые модули должны строиться только по следующему шаблону:**

```
src/Module/Billing/
├── Domain/
├── Application/
├── Infrastructure/
└── Integration/
```

**Подробный пример структуры модуля:**

```
src/Module/{ModuleName}/
├── Domain/
│   ├── Entity/
│   ├── ValueObject/
│   ├── Enum/
│   ├── Specification/
│   ├── Repository/
│   │   └── {RepositoryName}/
│   │       ├── Criteria/
│   │       │   ├── {RepositoryName}FindCriteria.php
│   │       │   └── ...
│   │       ├── {RepositoryName}CriteriaInterface.php
│   │       └── {RepositoryName}RepositoryInterface.php
│   ├── Service/
│   │   └── {ServiceGroup}/
│   │       └── {ServiceName}/
│   │           ├── {ServiceName}ServiceInterface.php
│   │           └── {ServiceName}Service.php
│   └── ...
├── Application/
│   ├── Dto/
│   ├── Enum/
│   ├── Event/
│   │   ├── {EventGroup}/
│   │   │   ├── {EventName}Event.php
│   │   │   └── ...
│   │   └── {EventGroup}EventInterface.php
│   ├── Mapper/
│   ├── UseCase/
│   │   ├── Command/
│   │   │   └── {CommandGroup}/
│   │   │       └── {CommandName}/
│   │   │           ├── {CommandName}Command.php
│   │   │           ├── {CommandName}CommandHandler.php
│   │   │           └── ...
│   │   └── Query/
│   │       └── {QueryGroup}/
│   │           └── {QueryName}/
│   │               ├── {QueryName}Query.php
│   │               ├── {QueryName}QueryHandler.php
│   │               └── ...
│   └── ...
├── Infrastructure/
│   ├── Component/
│   │   └── {ComponentGroup}/
│   │       └── {ComponentName}/
│   │           ├── Dto/
│   │           ├── Enum/
│   │           ├── Mapper/
│   │           ├── ValueObject/
│   │           ├── {ComponentName}ComponentInterface.php
│   │           ├── {ComponentName}Component.php
│   │           └── ...
│   ├── Model/
│   │   └── {ModelName}Model.php
│   ├── Repository/
│   │   └── {RepositoryName}/
│   │       ├── Criteria/
│   │       │   ├── Mapper/
│   │       │   └── CriteriaMapper.php
│   │       └── {RepositoryName}Repository.php
│   ├── Service/
│   │   └── {ServiceGroup}/
│   │       └── {ServiceName}/
│   │           └── {ServiceName}Service.php
│   └── ...
├── Integration/
│   ├── Component/
│   │   └── {ApiComponentGroup}/
│   │       └── {ApiComponentName}/
│   │           ├── Dto/
│   │           ├── Enum/
│   │           ├── Mapper/
│   │           ├── ValueObject/
│   │           ├── {ApiComponentName}ComponentInterface.php
│   │           ├── {ApiComponentName}Component.php
│   │           └── ...
│   ├── Listener/
│   │   └── {ExternalModuleName}/
│   │       └── {ListenerGroup}/
│   │           └── {EventName}Listener.php
│   └── Service/
│       └── {ServiceGroup}/
│           └── {ServiceName}/
│               └── {ServiceName}Service.php
├── Resource/
│   ├── config/
│   └── ...
└── {ModuleName}Module.php
```

Любое добавление новых подпапок — только при необходимости и с явным комментарием.

## Тесты

- **Unit tests**: размещать только в `/tests/Unit/`, для каждого класса Domain и Application.
- **Integration tests**: размещать только в `/tests/Integration/`, для всех реализаций Infrastructure и Integration.

**Необходимо** создавать подкаталоги, повторяющие структуру модулей, для тестов.
**Запрещено** смешивать разные типы тестов в одном каталоге.

Любой класс должен иметь покрытие тестом соответствующего типа (unit для Domain/Application, integration для Infrastructure/Integration).

## Architecture Guidelines

Архитектура каждого модуля строится на принципах Domain-Driven Design (DDD) и разделяется на четыре независимых слоя с
явными границами и запретами на несанкционированные зависимости.

### Layered Structure

- **Domain Layer** (`Domain/`)
    - Содержит только бизнес-логику: Entity, Value Object, доменные сервисы, интерфейсы репозиториев.
    - **Запрещено** зависеть от любых внешних библиотек и других слоев (допустимо использовать только стандартные типы,
      value objects и свои интерфейсы).
- **Application Layer** (`Application/`)
    - UseCases handlers, Application-сервисы, Command/Query DTO.
    - Организует выполнение бизнес-логики, связывает доменные сущности и сервисы.
    - **Не содержит** технических или инфраструктурных реализаций, не работает напрямую с внешними API.
- **Infrastructure Layer** (`Infrastructure/`)
    - Технические реализации внутренних зависимостей (например, реализации репозиториев для БД, логирование, файловая
      система, кэш).
    - **Не реализует интеграции с внешними сервисами**, не содержит межмодульного взаимодействия.
    - **Используется только для реализации интерфейсов Domain Layer**.
- **Integration Layer** (`Integration/`)
    - Вся работа с внешними системами, межмодульное взаимодействие, API клиентов, обработка внешних событий, очередь
      сообщений, адаптеры сторонних сервисов (антикоррупционный слой).
    - Может вызывать UseCases из Application Layer других модулей (рекомендуется использовать событийную модель).
    - **Здесь запрещается реализация инфраструктурных деталей**.

### Key Principles

1. **Модульная изоляция**
    - Модули не имеют прямых зависимостей друг на друга.
    - Общение между модулями — только через явно объявленные интерфейсы сервисов или через доменные/интеграционные
      события.
2. **Границы слоев**
    - Domain Layer **строго запрещено** иметь любые зависимости на другие слои.
    - Application Layer может зависеть только от Domain Layer (интерфейсы и value objects).
    - Infrastructure реализует только интерфейсы Domain Layer — **не реализует логику Application или Integration**.
    - Integration взаимодействует с Application Layer (своего или других модулей), но не содержит внутренней
      бизнес-логики или прямых реализаций интерфейсов домена.
3. **Передача данных**
    - **Только DTO** (final, readonly) разрешены для передачи данных между слоями. Использование массивов, mixed и примитивов — запрещено.
    - В слое Domain все значения бизнес-логики — Value Object.
    - Нельзя использовать примитивы для доменных сущностей или сервисов, только Value Object.
4. **Инверсии и зависимости**
    - **Инфраструктурный код всегда подключается только через интерфейсы**. Прямая связь с реализацией — запрещена.
    - Инъекция зависимостей происходит на уровне конфигурации приложения (DI-контейнер).
    - Реализации выбираются на уровне приложения, а не на уровне бизнес-логики.

> Любые отклонения от этих границ должны быть зафиксированы как технический долг и обсуждены в ревью.

### Anti-Corruption Layer (ACL)

- Слой Integration всегда реализует паттерн Anti-Corruption Layer — защищает доменную логику от изменений во внешних
  системах.
- Внешние данные и команды преобразуются во внутренние DTO перед передачей в Application Layer.

### Дополнительные рекомендации

- Все Value Object и DTO должны быть `final` и `readonly`.
- Все методы, пересекающие границы слоев, должны явно принимать и возвращать только строго типизированные объекты
  (никаких массивов, mixed или примитивов).
- Каждая реализация интеграции или инфраструктуры должна сопровождаться тестовым double (Fake, Mock, Stub).

### Примеры архитектурных ограничений

| Откуда         | Куда можно обращаться                   | Строго запрещено обращаться в...                  |
|----------------|-----------------------------------------|---------------------------------------------------|
| Domain         | ---                                     | Application, Infrastructure, Integration          |
| Application    | Domain                                  | Infrastructure, Integration                       |
| Infrastructure | Domain (interfaces only)                | Application, Integration                          |
| Integration    | Application, Application других модулей | Infrastructure, Domain (только через Application) |

> **Любое нарушение таблицы — отклонять без рассмотрения, кроме явно зафиксированных архитектурных решений.**

**Каждое изменение или новый код обязан полностью соответствовать этим правилам. Любое отклонение требует отдельного архитектурного решения, зафиксированного в коде и задаче.**

> ⚠️ Любое нарушение структуры, правил зависимостей, типизации или тестирования — критическая ошибка и должно быть зафиксировано в ревью с объяснением и пометкой @techdebt.
