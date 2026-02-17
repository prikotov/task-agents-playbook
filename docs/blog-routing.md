# Blog Front Controller and Routing Flow

Этот документ поясняет, почему при открытии домена `http://blog.task.prikotov.ru/` Symfony отдаёт страницу списка статей из `Blog\Module\Blog\Controller\ListingController::__invoke`.

## 1. Web-server (Nginx)

Nginx настроен на директорию `/var/www/task/apps/blog/public` и для всех запросов к корню (`location /`) выполняет директиву:

```nginx
try_files $uri /index.php$is_args$args;
```

Это значит, что любой путь, которого нет как статического файла, пробрасывается во фронт-контроллер `public/index.php`.

## 2. Фронт-контроллер Symfony

Файл `apps/blog/public/index.php` поднимает `BlogKernel` и инициирует HTTP-запрос в Symfony. Ядро загружает конфигурацию приложения, в том числе `apps/blog/config/routes.yaml`.

## 3. Подключение контроллеров модуля блога

В `apps/blog/config/routes.yaml` описан импорт атрибутивных маршрутов:

```yaml
blog_controllers:
    resource: ../src/Module/Blog/Controller/
    type: attribute
```

Symfony сканирует контроллеры из каталога `apps/blog/src/Module/Blog/Controller/` и регистрирует маршруты, объявленные атрибутами `#[Route(...)]`.

## 4. Маршрут домашней страницы

`ListingController` содержит атрибут:

```php
#[Route(path: BlogRoute::LIST_PATH, name: BlogRoute::LIST, methods: ['GET'])]
```

i константа `BlogRoute::LIST_PATH` равна `'/'`. Поэтому при запросе на `/` Symfony находит этот маршрут и вызывает `ListingController::__invoke`, который рендерит шаблон `@blog.blog/blog/listing.html.twig`.

Таким образом, связка Nginx → `index.php` → импорт атрибутивных маршрутов → маршрут `'/'` обеспечивает открытие списка статей по умолчанию.
