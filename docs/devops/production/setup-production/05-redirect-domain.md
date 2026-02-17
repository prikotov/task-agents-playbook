# 05. Редирект домена на другой домен (HTTPS)

Инструкция по настройке постоянного редиректа с альтернативного домена на основной на том же сервере с выпуском и автопродлением SSL‑сертификата.

Ниже пример для редиректа `aiaid.pro` → `ai-aid.pro`.

## Предпосылки
- DNS‑записи `A/AAAA` для альтернативного домена `aiaid.pro` и `www.aiaid.pro` указывают на ваш сервер.
- На сервере установлен nginx и есть доступ `root`.
- Установлен Certbot с nginx‑плагином (см. «01. Подготовка production-server»).

## Шаги

### 1) Временный HTTP‑виртуалхост для валидации
Создайте конфиг, чтобы certbot смог подтвердить домен по HTTP. Это безопасно: на время выпуска сертификата редирект идёт на HTTPS того же домена.

`/etc/nginx/conf.d/aiaid.pro.conf`:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name aiaid.pro www.aiaid.pro;
    return 301 https://aiaid.pro$request_uri;
}
```

Примените конфиг:
```bash
sudo nginx -t && sudo systemctl reload nginx
```

Пояснение:
- `nginx -t` — проверяет синтаксис конфигурации nginx и валидность include’ов.
- `&& sudo systemctl reload nginx` — перезагружает конфигурацию без даунтайма; выполняется только если проверка прошла успешно.

### 2) Выпуск SSL‑сертификата для альтернативного домена
```bash
sudo certbot --nginx -d aiaid.pro -d www.aiaid.pro
```
Автопродление включите по инструкции в разделе «06. SSL и безопасность».

Пояснение:
- `certbot --nginx -d …` — выпускает/обновляет сертификат Let’s Encrypt и настраивает nginx для доменов.
- Автопродление — см. «06. SSL и безопасность».

### 3) Финальный редирект на основной домен (HTTPS)
Замените конфиг на постоянный редирект с обоих протоколов (80/443) прямиком на целевой домен.

`/etc/nginx/conf.d/aiaid.pro.conf`:
```nginx
# HTTP → сразу на целевой домен (без лишней цепочки)
server {
    listen 80;
    listen [::]:80;
    server_name aiaid.pro www.aiaid.pro;
    return 301 https://ai-aid.pro$request_uri;
}

# HTTPS → редирект на целевой домен
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name aiaid.pro www.aiaid.pro;

    # Пути certbot (созданы автоматически)
    ssl_certificate     /etc/letsencrypt/live/aiaid.pro/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aiaid.pro/privkey.pem;

    # (опционально) ваши общие SSL‑параметры, если используются
    # include /etc/nginx/snippets/ssl-params.conf;

    return 301 https://ai-aid.pro$request_uri;
}
```

Примените конфиг:
```bash
sudo nginx -t && sudo systemctl reload nginx
```

Пояснение:
- `nginx -t` — проверка конфигурации.
- `reload nginx` — перечитать конфиг без остановки сервиса.

### 4) Проверка и диагностика
```bash
# Должны увидеть 301 на целевой домен
curl -I http://aiaid.pro/
curl -I https://aiaid.pro/

# Проверка автопродления
sudo certbot renew --dry-run
```

Пояснение:
- `curl -I http://…` — проверяет HTTP‑редирект (ожидается 301 с Location: https://ai-aid.pro/…).
- `curl -I https://…` — проверяет HTTPS‑редирект (ожидается 301 на целевой домен).
- `certbot renew --dry-run` — тест автопродления без реального запроса к CA.

## Примечания
- Для повышения безопасности можно добавить HSTS на целевом домене после того как удостоверитесь в корректности HTTPS.
