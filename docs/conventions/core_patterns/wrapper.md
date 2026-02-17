# Враппер (Wrapper)

**Враппер (Wrapper)** — класс для инкапсуляции доступа к данным другого класса/классов или для специального представления этих данных. Реализация паттерна Фасад, работающая только на чтение данных.

## Общие правила

- В названии класса указывается постфикс `Wrapper`.
- Принимает исходные данные в конструкторе: DTO, объект, массив, JSON.
- Логика получения данных находится в геттерах.
- Stateless (без состояния), кэширование запрещено.
- Не может быть входным или возвращаемым параметром между слоями — используется только для работы на конкретном слое.
- Внутри враппера не используется Dependency Injection. Сервисы, необходимые в геттерах, передаются в конструкторе при создании объекта.
- Логика использования сервисов должна быть максимально простой: вызвали сервис, передали параметры — вернули значение из геттера.
- Запрещено создавать вложенные врапперы. Для набора элементов, нуждающихся в оборачивании, создаётся фабрика, которая создаёт отдельный враппер для каждого элемента массива.

## Зависимости

**Разрешено**:
- DTO, VO, Entity своего модуля и слоя.
- Примитивы, Enum, DateTimeImmutable.
- Сервисы, формatters, converters — только через конструктор.
- Интерфейсы враппера (для разных источников данных с одинаковым представлением).

**Запрещено**:
- Изменение данных в БД.
- Бизнес-логика домена.
- Dependency Injection внутри враппера.
- Вложенные врапперы.
- Передача враппера между слоями как параметра или возвращаемого значения.
- Массивы без типизации — использовать DTO или shape-типы (`array{...}`).

## Расположение

**Presentation слой (Web)** — для представления данных в UI:

```
Web\Module\{ModuleName}\Wrapper\{Name}Wrapper
```

**Infrastructure слой** — для работы с инфраструктурными данными:

```
Common\Module\{ModuleName}\Infrastructure\Component\{Component}\{Name}Wrapper
```

**Application слой** — для представления данных в use case:

```
Common\Module\{ModuleName}\Application\Wrapper\{Name}Wrapper
```

## Как используем

- Для простого интерфейса доступа к данным (сокрытие лишней вложенности, соблюдение [Law of Demeter](https://en.wikipedia.org/wiki/Law_of_Demeter)).
- Для композиции и преобразования вида данных (представление данных в индивидуальном виде).
- Для форматирования данных для пользователя (HTML-теги, виджеты, текстовые представления).
- Враппер используется только внутри слоя, где создан. Не передаётся между слоями — для этого используются DTO и мапперы.
- Если враппер имеет множество зависимостей и содержит объёмные операции по сбору данных или бизнес-логику — рекомендуется уйти от враппера и разнести логику по разным уровням (DTO + Mapper).
- Врапперы имеют тенденцию превращаться в god-objects — следите за размером и сложностью.

### Когда используем

1. Необходим простой интерфейс для доступа к данным (сокрытие лишней вложенности).
2. Композиция и преобразование вида данных для конкретного места использования.
3. Представление данных пользователю (форматирование, HTML-теги, виджеты).

### Когда не используем

- Для передачи данных между слоями — используйте DTO.
- Для сложной бизнес-логики — используйте Service или Calculator.
- Для маппинга данных между слоями — используйте Mapper.
- Если враппер содержит множество зависимостей и объёмную логику.

## Пример

### Пример 1: Враппер для представления проекта (Presentation слой)

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\Wrapper;

use Common\Module\Project\Application\Dto\ProjectDto;
use Common\Module\Project\Application\Enum\ProjectStatusEnum;

final readonly class ProjectDisplayWrapper
{
    public function __construct(
        private ProjectDto $projectDto,
    ) {
    }

    public function getDisplayTitle(): string
    {
        $title = $this->projectDto->title;

        if ($this->projectDto->status === ProjectStatusEnum::Closed) {
            $title .= ' [Closed]';
        }

        return $title;
    }

    public function getFormattedDiskSize(): string
    {
        return $this->formatBytes($this->projectDto->diskSize);
    }

    public function getCreatorDisplayName(): string
    {
        if ($this->projectDto->creator === null) {
            return 'Unknown';
        }

        return $this->projectDto->creator->username;
    }

    public function isPublished(): bool
    {
        return $this->projectDto->isPublished;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

### Пример 2: Использование враппера в форме (Presentation слой)

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\Form;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Project\Application\Dto\ProjectDto;
use Common\Module\Project\Application\UseCase\Query\Project\Find\FindQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Web\Module\Project\Wrapper\ProjectDisplayWrapper;

final readonly class ProjectSelectFormType extends AbstractType
{
    public function __construct(
        private QueryBusComponentInterface $queryBus,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $query = new FindQuery(userId: $options['user_id']);
        $result = $this->queryBus->query($query);

        $choices = ['' => ''];
        foreach ($result->items as $projectDto) {
            $wrapper = new ProjectDisplayWrapper($projectDto);
            $choices[$wrapper->getDisplayTitle()] = $projectDto->uuid->toString();
        }

        $builder->add('project', ChoiceType::class, [
            'choices' => $choices,
            'placeholder' => 'Select project',
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user_id' => null,
        ]);

        $resolver->setAllowedTypes('user_id', ['int', 'null']);
    }
}
```

### Пример 3: Враппер для инфраструктурных данных (Infrastructure слой)

```php
<?php

declare(strict_types=1);

namespace Common\Module\Project\Infrastructure\Wrapper;

use Common\Module\Project\Domain\Entity\ProjectModel;

final readonly class ProjectDiskUsageWrapper
{
    public function __construct(
        private ProjectModel $projectModel,
    ) {
    }

    public function getTotalSize(): int
    {
        return $this->projectModel->getArtifactsSize()
            + $this->projectModel->getDocumentsSize()
            + $this->projectModel->getDocumentChunksSize();
    }

    public function getFormattedTotalSize(): string
    {
        return $this->formatBytes($this->getTotalSize());
    }

    public function getArtifactsPercentage(): float
    {
        $total = $this->getTotalSize();

        return $total > 0 ? ($this->projectModel->getArtifactsSize() / $total) * 100 : 0.0;
    }

    public function getDocumentsPercentage(): float
    {
        $total = $this->getTotalSize();

        return $total > 0 ? ($this->projectModel->getDocumentsSize() / $total) * 100 : 0.0;
    }

    public function getChunksPercentage(): float
    {
        $total = $this->getTotalSize();

        return $total > 0 ? ($this->projectModel->getDocumentChunksSize() / $total) * 100 : 0.0;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

## Чек-лист код-ревью

- [ ] Класс имеет постфикс `Wrapper` и находится в правильном слое.
- [ ] Класс помечен как `final` и `readonly`.
- [ ] Исходные данные принимаются в конструкторе.
- [ ] Логика получения данных находится в геттерах.
- [ ] Нет изменения данных в БД и бизнес-логики домена.
- [ ] Внутри враппера не используется Dependency Injection (сервисы через конструктор).
- [ ] Нет вложенных врапперов.
- [ ] Враппер не передаётся между слоями как параметр или возвращаемое значение.
- [ ] Для набора элементов используется фабрика, а не массив врапперов.
- [ ] Враппер не превращается в god-object (множество зависимостей, объёмная логика).
- [ ] При наличии сложной логики рассмотрен переход на DTO + Mapper.
