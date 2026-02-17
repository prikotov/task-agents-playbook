# 04. Блог: домен blog.ai-aid.pro

Этот раздел описывает настройку отдельного домена для блога. Приложение блога находится в каталоге `apps/blog`, а корнем для веб‑сервера должен быть `apps/blog/public`.

## 1) DNS
- Создайте A/AAAA запись `blog.ai-aid.pro` на IP вашего сервера.

## 2) Nginx vhost
Ниже пример конфигурации для Nginx. Отредактируйте пути в соответствии с реальным путём деплоя проекта (пример предполагает `/var/www/task` как корень репозитория).

```nginx
server {
    server_name blog.ai-aid.pro;

    root /var/www/task/apps/blog/public;
    index index.php;

    access_log /var/log/nginx/blog.ai-aid.pro.access.log;
    error_log  /var/log/nginx/blog.ai-aid.pro.error.log warn;

    # Отдаём статические файлы напрямую, остальное — во фронт‑контроллер
    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~* \.(?:ico|png|jpg|jpeg|svg|gif|css|js|woff2?)$ {
        expires 7d;
        access_log off;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        # Используйте сокет/порт вашего PHP‑FPM
        fastcgi_pass unix:/run/php/php-fpm.sock; # или: unix:/run/php/php8.3-fpm.sock
    }
}
```

Применение и проверка:

```bash
sudo ln -s /etc/nginx/sites-available/blog.ai-aid.pro.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 3) SSL (Let's Encrypt)
После проверки, что домен отрабатывает по HTTP, выпустите сертификат:

```bash
sudo certbot --nginx -d blog.ai-aid.pro
```

Подробнее про авто‑продление и проверку — в разделе «06. SSL и безопасность» (`06-ssl-security.md`).

## 4) Примечания по Symfony
- Фронт‑контроллер `apps/blog/public/index.php` поднимает `Blog\Kernel` и маршрутизацию блога.
- Общее устройство маршрутов блога описано в `docs/blog-routing.md`.
- Кэш и окружение — production. После деплоя при необходимости прогрейте кэш.

```bash
php bin/console cache:warmup --env=prod
```
