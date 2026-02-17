# 11. Проверки после настройки и запуска

- [ ] `APP_ENV=prod`, `APP_DEBUG=0`.
- [ ] Секреты заданы (`APP_SECRET`, токены).
- [ ] База данных доступна: `task_db` и расширение `vector` включено.
- [ ] Файлы в `/mnt/task/files`, владелец `wwwtask`.
- [ ] Конфиги валидны: `nginx -t` → OK.
- [ ] Перезагружен `php-fpm`.
- [ ] Memcached доступен и `ext-memcached` установлен: `php --ri memcached` (версия >= 3.1.6).
- [ ] Smoke‑test платформы: `curl -I https://task.ai-aid.pro/` (200/302).
- [ ] Редирект алиаса (если используется):
      `curl -I http://aiaid.pro/` и `curl -I https://aiaid.pro/` → Location: `https://ai-aid.pro/...` (301).
- [ ] Автопродление SSL включено и проверено: `systemctl is-enabled certbot-renew.timer` = enabled,
      `certbot renew --dry-run` проходит.

Диагностика (быстрые проверки)
```bash
sudo tail -n 200 /var/log/nginx/task-error.log
sudo -u wwwtask php /var/www/task/bin/console dbal:run-sql "SELECT 1" -e=prod
sudo -u wwwtask php --ri memcached
curl -I https://task.ai-aid.pro/
```
