# Представление (View)

**Представление (View)** — слой, отвечающий за отображение данных пользователю через HTML-шаблоны, использующие Twig и Bootstrap 5 Phoenix. Подробнее см. руководство [Symfony Twig](https://symfony.com/doc/current/templates.html), [Symfony UX TwigComponent](https://symfony.com/bundles/ux-twig-component/current/index.html) и документацию [Bootstrap 5 Phoenix](../theme/README.md).

## Общие правила

- Шаблоны храним в `apps/<app>/src/Module/<ModuleName>/Resource/templates/` или в `templates/` для общих шаблонов.
- Используем синтаксис Twig с расширениями Symfony UX (TwigComponent).
- Все текстовые строки для UI оборачиваем в фильтр `|trans` для локализации.
- Для переиспользуемых UI-элементов используем компоненты Symfony UX TwigComponent (Bootstrap 5 Phoenix).
- Передача данных из контроллера — через ассоциативный массив вторым аргументом `$this->render()`.
- Для частичных шаблонов используем директиву `include` с параметром `only` для изоляции контекста.
- Запрещено: бизнес-логика в шаблонах, прямые вызовы сервисов, доступ к репозиториям.

## Зависимости

- Разрешено: данные из контроллера (DTO, простые типы), Twig-функции, фильтры, компоненты Phoenix.
- Запрещено: прямое обращение к сервисам, репозиториям, доменным сущностям, Application-UseCase.

## Расположение

```
apps/<app>/src/Module/<ModuleName>/Resource/templates/<context>/<action>.html.twig
templates/<module>/<template>.html.twig  # общие шаблоны
```

## Как используем

1. Шаблон наследует базовый шаблон через `{% extends 'base.html.twig' %}`.
2. Переопределяем блоки: `title`, `stylesheets`, `body`, `javascripts`.
3. Данные передаём из контроллера через массив: `$this->render('template.html.twig', ['key' => $value])`.
4. Для переиспользуемых фрагментов используем `include` с явным перечислением параметров и `only`.
5. Для сложных UI-элементов используем компоненты Phoenix: `<twig:Phoenix:Alert ...>`, `<twig:Phoenix:Pagination ...>`.
6. Для локализации используем фильтр `|trans` с ключами переводов.

## Пример

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ 'user.user.list.title'|trans }} ({{ total }}){% endblock %}

{% block body %}
<div class="pb-6">
    <div class="row mb-4 align-items-center">
        <div class="col-auto">
            <h2 class="mb-0">{{ 'user.user.list.heading'|trans }} <span class="fw-normal text-body-tertiary">({{ total }})</span></h2>
        </div>
        <div class="col-auto">
            <a class="btn btn-primary" href="{{ userRoute.create }}">
                <span class="fas fa-plus me-2"></span>{{ 'user.user.actions.add'|trans }}
            </a>
        </div>
    </div>

    <div class="row g-3 justify-content-between mb-4">
        <div class="col-auto"></div>
        <div class="col-auto">
            <div class="d-flex">
                {% include '@web.user/user/_search.html.twig' with { filterForm: filterForm } only %}
                {% include '@web.user/user/_filter.html.twig' with { filterForm: filterForm } only %}
            </div>
        </div>

        <div class="col-12">
            {% include '@web.user/user/_table.html.twig' with {
                users: users,
                pagination: pagination,
                sort: sort,
                userRoute: userRoute,
                planRoute: planRoute,
                filter: filter,
                total: total,
                userGrant: userGrant
            } only %}
        </div>
    </div>
</div>
{% endblock %}
```

## Компоненты Bootstrap 5 Phoenix

Проект использует Symfony UX TwigComponent для переиспользуемых UI-элементов на основе Bootstrap 5 Phoenix. Компоненты расположены в `apps/web/src/Component/Twig/Phoenix/`.

### Основные компоненты

- **Alert** — уведомления с иконками и вариантами (primary, success, danger, warning, info).
- **Pagination** — постраничная навигация с поддержкой сортировки и фильтров.
- **DropdownActions** — выпадающее меню действий в таблицах.
- **Badge** — бейджи для статусов.
- **NavLink**, **NavList**, **NavListItem** — навигационные элементы.
- **Spinner** — индикатор загрузки.
- **Tooltip** — всплывающие подсказки.

### Пример использования компонента

```twig
<twig:Phoenix:Alert
    type="success"
    variant="phoenix"
    :dismissible="true"
    :showIcon="true"
    message="{{ 'flash.success'|trans }}"
/>
```

```twig
<twig:Phoenix:Pagination
    :route="sourceRoute.list"
    :page="pagination.page"
    :perPage="pagination.perPage"
    :total="total"
    :sort="sort.field"
    :filter="filter"
/>
```

## Передача данных из контроллеров

Данные передаются через ассоциативный массив вторым аргументом метода `render()`:

```php
return $this->render('@web.user/user/list.html.twig', [
    'users' => $users,
    'pagination' => $pagination,
    'sort' => $sort,
    'userRoute' => $this->userRoute,
    'planRoute' => $this->planRoute,
    'filter' => $filter,
    'total' => $total,
    'userGrant' => $this->userGrant,
]);
```

В шаблоне данные доступны по ключам массива. Рекомендуется передавать только DTO и простые типы.

## Переиспользование шаблонов

### Include

Для включения частичных шаблонов используем `include` с параметром `only`:

```twig
{% include '@web.user/user/_table.html.twig' with {
    users: users,
    pagination: pagination,
    sort: sort,
    userRoute: userRoute,
    filter: filter,
    total: total
} only %}
```

Параметр `only` изолирует контекст включаемого шаблона, предотвращая случайный доступ к переменным родительского шаблона.

### Twig Components

Для сложных переиспользуемых элементов используем Symfony UX TwigComponent:

```php
#[AsTwigComponent]
final class Alert
{
    public string $type = self::PRIMARY;
    public string $variant = self::VARIANT_OUTLINE;
    public string $message = '';
    public bool $dismissible = true;
    public bool $showIcon = true;
}
```

```twig
<twig:Phoenix:Alert
    type="danger"
    :dismissible="false"
    message="{{ 'error.message'|trans }}"
/>
```

## Лучшие практики

- Изолируйте контекст включаемых шаблонов через `only`.
- Используйте компоненты Phoenix для UI-элементов вместо дублирования HTML.
- Все текстовые строки оборачивайте в `|trans` для локализации.
- Не размещайте бизнес-логику в шаблонах — делегируйте её в контроллер или Application.
- Для сложных вычислений в шаблоне создавайте Twig-расширения или Runtime-классы.
- Используйте семантические имена переменных, отражающие их назначение.
- Группируйте связанные шаблоны в поддиректории по контексту.

## Чек-лист для проведения ревью кода

- [ ] Шаблон хранится в правильной директории `Resource/templates/`.
- [ ] Все текстовые строки обёрнуты в `|trans`.
- [ ] Для включаемых шаблонов используется параметр `only`.
- [ ] Для UI-элементов используются компоненты Phoenix.
- [ ] Нет бизнес-логики и прямых обращений к сервисам.
- [ ] Данные передаются из контроллера через массив.
- [ ] Шаблон наследует `base.html.twig` или другой базовый шаблон.
