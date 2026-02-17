# Application Structure

## Applications

- **Web**: `/apps/web/` — Веб-интерфейс, контроллеры, публичные entrypoints.
- **Console**: `/apps/console/` — CLI-команды, задачи для крон-джобов.
- **API**: `/apps/api/` — Внешние API контроллеры, схемы и роутинг.

**Пример структуры `/apps/web/`:**

```
/apps/web/
├── config/
├── src/
│   ├── Component/
│   ├── Module/
│   │   └── {ModuleName}/
│   │       ├── Controller/
│   │       │   ├── {SubjectName}/
│   │       │   │   ├── {ActionName}Controller.php
│   │       │   │   └── ...
│   │       │   └── ...
│   │       ├── Enum/
│   │       ├── Form/
│   │       │   ├── {SubjectName}/
│   │       │   │   ├── {FormName}FormModel.php
│   │       │   │   ├── {FormName}FormType.php
│   │       │   │   └── ...
│   │       │   └── ...
│   │       ├── List/
│   │       ├── Map/
│   │       ├── Mapper/
│   │       ├── Resource/
│   │       │   ├── config/
│   │       │   │   ├── services.yaml
│   │       │   │   └── ...
│   │       │   ├── templates/
│   │       │   │   └── subject_name
│   │       │   │       ├── {action_name}.html.twig
│   │       │   │       └── ...
│   │       │   └── translations/
│   │       ├── Route/
│   │       │   └── {SubjectName}Route.php
│   │       ├── Security/
│   │       │   ├── {SubjectName}/
│   │       │   │   ├── ActionEnum.php
│   │       │   │   ├── Grant.php
│   │       │   │   ├── PermissionEnum.php
│   │       │   │   ├── Rule.php
│   │       │   │   └── Voter.php
│   │       │   └── ...
│   │       ├── ...
│   │       └── {ModuleName}Module.php
│   └── ...
├── templates/
└── tests/
```

## Тесты

- **Application-specific tests**: `/apps/*/tests/` — Тесты для приложений (web, console, api).

---

# Guidelines: List Page Template

Этот файл описывает типовой шаблон для страниц со списком элементов в приложении `apps/web`.

## Расположение элементов
- **Заголовок** (`<h2>`): выводит название раздела и количество элементов.
- **Кнопка добавления** (при наличии) располагается рядом с заголовком.
- **Поле поиска** и кнопка фильтра размещены справа от заголовка. Их разметка подключается шаблонами `_search.html.twig` и `_filter.html.twig`.
- **Модальное окно фильтра** (часть шаблона `_filter.html.twig`) открывается по нажатию кнопки фильтра и содержит форму `FilterFormType`.
- **Таблица** выводится под блоком управления и подключается через `_table.html.twig`.
- **Пагинация** находится под таблицей и реализуется компонентом `Phoenix:Pagination`.

## Структура файлов
- `list.html.twig` — основной шаблон страницы. Подключает:
  - `_search.html.twig` — форма поиска.
  - `_filter.html.twig` — кнопка и модальное окно фильтрации.
  - `_table.html.twig` — таблица со строками и пагинацией.

### Примеры шаблонов

#### `list.html.twig`

- apps/web/src/Module/Source/Resource/templates/source/list.html.twig
- apps/web/src/Module/Llm/Resource/templates/mcp_server/list.html.twig

## Структура контроллера
- Роут `[GET] /items` (`ItemRoute::LIST`).
- В конструктор внедряются мапперы пагинации и сортировки, `QueryBus` и `ItemRoute`.
- В методе `__invoke` создаётся и обрабатывается `FilterFormType`.
- Выполняется `FindQuery` для получения элементов.
- В шаблон передаются параметры:
  - `items`, `total`, `pagination`, `sort`;
  - `filterForm`, `filter`, `itemRoute`.

### Примеры контроллеров

- apps/web/src/Module/Source/Controller/Source/ListController.php
- apps/web/src/Module/Llm/Controller/McpServer/ListController.php

## Роутинг

Роуты хранятся и формируются в классах apps/*/src/Module/{ModuleName}/{GroupName}/{RouteName}Route.php

### Примеры класса 
apps/web/src/Module/Source/Route/SourceRoute.php
