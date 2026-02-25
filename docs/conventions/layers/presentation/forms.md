# Форма презентационного слоя (Presentation Form)

## Определение

**Форма презентационного слоя (Presentation Form)** — набор объектов, отвечающих за ввод и валидацию данных
до вызова Application-UseCase. Базируемся на [Symfony Forms](https://symfony.com/doc/current/forms.html).

## Общие правила

- Разделяем данные (`FormModel`), описание полей (`FormType`) и представление (Twig-шаблон).
- Формы объявляем `final` и строго типизируем поля, используем PHPDoc `@template-extends AbstractType<…>`.
- Для фильтров включаем метод `GET` и отключаем CSRF; для действий (`create`, `edit`) используем `POST`.
- Переводы (`label`, `help`, `placeholder`) задаём через `translation_domain` либо ключи в YAML.
- В контроллере проверяем `isSubmitted()` до `isValid()`, работаем с типизированной моделью.

## Зависимости

- Разрешено: сервисы Presentation (списки значений, мапперы), перечисления слоя, DTO Application в качестве данных.
- Запрещено: репозитории, сервисы Domain/Infrastructure, глобальные синглтоны.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Form/<Context>/<Name>FormModel.php
apps/<app>/src/Module/<ModuleName>/Form/<Context>/<Name>FormType.php
apps/<app>/src/Module/<ModuleName>/Resource/templates/<context>/_*.html.twig
```

## Как используем

1. Создаём `FormModel` с начальными данными (для фильтров — пустой объект).
2. Контроллер вызывает `createForm(FormType::class, $model)` и обрабатывает запрос.
3. После успешной валидации берём данные из модели и передаём в Application-UseCase.
4. Шаблон отображает форму через `form_start`/`form_widget`, опционально подключая Phoenix-компоненты.
5. Для фильтров сериализуем состояние в query string через вспомогательные методы модели.

## Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\Billing\Form\Plan;

use Common\Module\Billing\Application\Enum\PlanStatusEnum;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Web\Module\Billing\Enum\CurrencyEnum;
use Web\Module\Billing\List\CurrencyList;

final class FilterFormModel
{
    public function __construct(
        private ?string $search = null,
        private ?string $modelName = null,
        private ?CurrencyEnum $currency = null,
        private ?PlanStatusEnum $status = null,
    ) {
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(?string $search): void
    {
        $this->search = $search;
    }

    public function getModelName(): ?string
    {
        return $this->modelName;
    }

    public function setModelName(?string $modelName): void
    {
        $this->modelName = $modelName;
    }

    public function getCurrency(): ?CurrencyEnum
    {
        return $this->currency;
    }

    public function setCurrency(?CurrencyEnum $currency): void
    {
        $this->currency = $currency;
    }

    public function getStatus(): ?PlanStatusEnum
    {
        return $this->status;
    }

    public function setStatus(?PlanStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function toQueryParams(?string $prefix = null): array
    {
        $params = [];
        if ($this->search !== null) {
            $params[$prefix === null ? 'search' : "{$prefix}[search]"] = $this->search;
        }
        if ($this->modelName !== null) {
            $params[$prefix === null ? 'modelName' : "{$prefix}[modelName]"] = $this->modelName;
        }
        if ($this->currency !== null) {
            $params[$prefix === null ? 'currency' : "{$prefix}[currency]"] = $this->currency->value;
        }
        if ($this->status !== null) {
            $params[$prefix === null ? 'status' : "{$prefix}[status]"] = $this->status->value;
        }
        return $params;
    }
}

/**
 * @template-extends AbstractType<FilterFormModel>
 */
final class FilterFormType extends AbstractType
{
    public function __construct(private readonly CurrencyList $currencyList)
    {
    }

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'billing.plan.labels.search',
            ])
            ->add('modelName', TextType::class, [
                'required' => false,
                'label' => 'billing.plan.labels.modelName',
            ])
            ->add('currency', EnumType::class, [
                'required' => false,
                'class' => CurrencyEnum::class,
                'choices' => $this->currencyList->getList(),
                'label' => 'billing.plan.labels.currency',
            ])
            ->add('status', EnumType::class, [
                'required' => false,
                'class' => PlanStatusEnum::class,
                'label' => 'billing.plan.labels.status',
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FilterFormModel::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
```

## Получение списков через QueryBus

Для заполнения select-полей (проекты, пользователи, тарифы) FormType может внедрять `QueryBusComponentInterface`.

### Когда использовать

- Список значений зависит от прав текущего пользователя или других runtime-условий.
- Список получается через Application Query (а не статический enum).

### Пример

```php
<?php

declare(strict_types=1);

namespace Web\Module\Project\Form\Task;

use Common\Application\Component\QueryBus\QueryBusComponentInterface;
use Common\Module\Project\Application\UseCase\Query\Project\FindForSelect\FindForSelectQuery;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<CreateFormModel>
 */
final class CreateFormType extends AbstractType
{
    public function __construct(
        private readonly QueryBusComponentInterface $queryBus,
    ) {
    }

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $projects = $this->queryBus->query(new FindForSelectQuery());

        $builder
            ->add('projectUuid', ChoiceType::class, [
                'choices' => $projects->items,
                'choice_value' => 'uuid',
                'choice_label' => 'name',
                'label' => 'task.labels.project',
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateFormModel::class,
        ]);
    }
}
```

### Кэширование списков

Для часто используемых списков применяйте кэширование на уровне QueryHandler или используйте List-сервисы (см. [List-сервисы](list-controller.md)).

## Чек-лист для проведения ревью кода

- [ ] FormModel и FormType лежат в каталоге `Form` и объявлены `final`.
- [ ] Поля формы типизированы, отсутствуют прямые зависимости от Domain/Infrastructure.
- [ ] Формы фильтров используют метод `GET` и сериализуют состояние в query string.
- [ ] Контроллер получает типизированную модель и проверяет `isSubmitted()`/`isValid()`.
- [ ] Twig-шаблон подключает тему и выключает `render_rest`, если поля рендерятся вручную.
- [ ] QueryBus в FormType используется только для динамических списков, не для бизнес-логики.
