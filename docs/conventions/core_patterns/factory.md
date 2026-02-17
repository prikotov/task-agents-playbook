# Фабрика (Factory)

**Фабрика (Factory)** — класс, отвечающий за создание объектов своего слоя, когда одного конструктора недостаточно, требуется централизовать проверку инвариантов или скрыть сложность сборки и зависимостей.

## Общие правила

- В названии класса указывается постфикс `Factory`.
- Фабрика возвращает объекты (Entity, VO, DTO).
- Массивы запрещены, допускаются только строго типизированные коллекции (iterable<T>, Collection<T>) с явным интерфейсом.
- Фабрика не должна содержать бизнес-логику домена — только логику создания.
- Фабрика не должна изменять состояние базы данных или выполнять I/O-операции.
- При некорректных входных данных/нарушении инвариантов выбрасываются корректные [исключения](exception.md).
- Фабрики подключаются через DI-контейнер.

## Зависимости

**Разрешено**:
- Примитивы (только конфигурационные), Enum, VO своего модуля.
- DTO — только в Application/Presentation, не в Domain.
- Через DI Спецификации, другие фабрики своего модуля.

**Запрещено**:
- Зависимости от более внешних слоёв (Domain → Application / Infrastructure / Presentation)
- Любое взаимодействие с БД.
- Внешние HTTP-клиенты, SDK, API-вызовы.
- Глобальное состояние, статические синглтоны.
- Логика бизнес-процессов.

## Расположение

Фабрика размещается в том слое, объекты которого она создаёт. Примеры:

**Domain слой** — фабрики для создания сущностей и VO:

```
Common\Module\{ModuleName}\Domain\Factory\{Name}Factory
```

**Application слой** — фабрики для DTO и объектов use case:

```
Common\Module\{ModuleName}\Application\Factory\{Name}Factory
Common\Module\{ModuleName}\Application\UseCase\Command\{UseCase}\{Name}Factory
Common\Module\{ModuleName}\Application\UseCase\Query\{UseCase}\{Name}Factory
Common\Module\{ModuleName}\Application\Service\{ServiceName}\{Name}Factory
```

## Пример

```php
<?php

declare(strict_types=1);

namespace Common\Module\Llm\Domain\Factory;

use Common\Module\Llm\Domain\Enum\ToolEnum;
use Common\Module\Llm\Domain\Enum\ToolTypeEnum;
use Common\Module\Llm\Domain\Service\ToolFunction\ToolFunctionRegistryInterface;
use Common\Module\Llm\Domain\ValueObject\ToolFunctionVo;
use Common\Module\Llm\Domain\ValueObject\ToolVo;

final readonly class ToolVoFactory
{
    public function __construct(
        private ToolFunctionRegistryInterface $registry,
    ) {
    }

    public function create(ToolEnum $name): ToolVo
    {
        $tool = $this->registry->get($name);

        return new ToolVo(
            type: ToolTypeEnum::function,
            function: new ToolFunctionVo(
                name: $tool->getName(),
                description: $tool->getDescription(),
                parameters: $tool->getParameters(),
            ),
            callback: $tool,
            server: null,
        );
    }
}
```

## Чек-лист для ревью кода

- [ ] Класс имеет постфикс `Factory` и находится в правильном слое.
- [ ] Методы возвращают типизированные объекты (Entity, VO, DTO), не `void` или массивы.
- [ ] Нет изменений состояния БД и I/O-операций внутри фабрики.
- [ ] При нарушении инвариантов выбрасывается исключения.
- [ ] Зависимости внедряются через конструктор, нет глобального состояния.
- [ ] Логика создания отделена от бизнес-логики домена.
- [ ] Для сложной логики создания используется отдельный класс-фабрика.
- [ ] Unit-тесты обязательны при наличии ветвлений, вариантов создания и исключений.
