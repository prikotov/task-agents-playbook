# 1. Подготовка production-server

1. Установить PHP 8.4 + php-fpm, PostgreSQL 17 (+ pgvector), Podman.  
2. Создать пользователя `wwwtask`, включить subuid/subgid.  
3. Настроить PHP-FPM пул:  
   - `wwwtask` → web, api и docs.  
4. Настроить nginx для всех доменов.  
5. Установить Certbot (Let's Encrypt) и nginx-плагин:  
   ```bash
   sudo dnf install -y certbot python3-certbot-nginx
   ```

# Установка и обновление PHP 8.4

```
# Подключить репозитории
sudo dnf install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-9.noarch.rpm
# sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
sudo dnf install https://rpms.remirepo.net/enterprise/remi-release-$(rpm -E %{rhel}).rpm -y
sudo dnf makecache

# Сбросить и включить модуль PHP 8.4
sudo dnf module reset -y php
sudo dnf module enable -y php:remi-8.4

# Установить PHP и необходимые расширения
sudo dnf install php-cli php-fpm php-opcache php-intl php-mbstring php-xml php-pdo php-pgsql php-zip php-curl php-gd php-pecl-memcached
  
# php-mysqlnd
# php тянет apache 

# Проверить версию
php -v
```

Memcached используется Symfony Cache в `prod`, поэтому на web/api нодах требуется `ext-memcached` (см. [../../memcached.md](../../memcached.md)).


```
sudo dnf install -y php-bcmath
```

# Настройка пользователя `wwwtask`

Для создания пользователя `wwwtask` следуйте инструкции в документе [Создание пользователя wwwtask](../wwwtask-user-setup.md).

# Установка и настройка Podman

```
# Установить podman и зависимости
sudo dnf install -y podman fuse-overlayfs shadow-utils-subid

# Проверить версию
podman --version

# Проверить rootless от имени wwwtask
sudo -u wwwtask -H bash -lc 'cd ~ && podman info --debug | head -n 20'
sudo -u wwwtask -H bash -lc 'cd ~ && podman run --rm quay.io/podman/hello'
```


# Настройка пула php-fpm для пользователя `wwwtask`

Создайте файл `/etc/php-fpm.d/wwwtask.conf`:

```ini
[wwwtask]
user = wwwtask
group = wwwtask
listen = /run/php-fpm/wwwtask.sock
listen.acl_users = nginx

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35

; важно для rootless podman
clear_env = no
env[PATH] = /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
env[XDG_RUNTIME_DIR] = /run/user/981   ; подставить UID wwwtask на текущем сервере
```

Примените изменения:

```bash
sudo systemctl restart php-fpm
ls -l /run/php-fpm/wwwtask.sock
```
