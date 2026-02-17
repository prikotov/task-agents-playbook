# 2. Установка и первый деплой

```bash
# git
sudo mkdir -p /var/www && sudo chown -R wwwtask:wwwtask /var/www
sudo -u wwwtask git clone git@github.com:prikotov/TasK.git /var/www/task

# .env.local (prod)
sudo -u wwwtask bash -lc 'cat > /var/www/task/.env.local <<EOF
APP_ENV=prod
APP_DEBUG=0
DATABASE_URL="postgresql://task_user:***@127.0.0.1:5432/task_db?serverVersion=17&charset=utf8"
TRUSTED_HEADERS=x-forwarded-all
EOF'

# зависимости и сборка
sudo -u wwwtask composer install --no-dev --optimize-autoloader -d /var/www/task
sudo -u wwwtask make -C /var/www/task install
```

