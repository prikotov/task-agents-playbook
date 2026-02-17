# 03. Основной домен: ai-aid.pro

Цель: настроить основной домен `ai-aid.pro` с постоянным редиректом на платформу `task.ai-aid.pro`.

Примечание: при появлении отдельного лендинга правило редиректа можно заменить на отдачу статического или Symfony-приложения.

## DNS
- Создайте A/AAAA-записи `ai-aid.pro` и `www.ai-aid.pro` на IP вашего сервера.

## Конфигурация Nginx
Пример финальной конфигурации с редиректом на целевой домен по обоим протоколам. Certbot добавит SSL-пути автоматически.

`/etc/nginx/conf.d/ai-aid.pro.conf`:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name ai-aid.pro www.ai-aid.pro;
    return 301 https://task.ai-aid.pro$request_uri;
}

# После выпуска сертификата certbot добавит SSL-настройки
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ai-aid.pro www.ai-aid.pro;

    # ssl_certificate /etc/letsencrypt/live/ai-aid.pro/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/ai-aid.pro/privkey.pem;

    return 301 https://task.ai-aid.pro$request_uri;
}
```

Примените конфигурацию:
```bash
sudo nginx -t && sudo systemctl reload nginx
```

## SSL (Let’s Encrypt)
Выпустите сертификат:
```bash
sudo certbot --nginx -d ai-aid.pro -d www.ai-aid.pro
```
Автопродление и проверка — см. «06. SSL и безопасность».

## Проверка
```bash
curl -I http://ai-aid.pro/
curl -I https://ai-aid.pro/
```
Ожидается 301-редирект на `https://task.ai-aid.pro/…`.
