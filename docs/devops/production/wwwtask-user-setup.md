# Создание пользователя wwwtask

Инструкция по созданию системного пользователя `wwwtask` и подготовке SSH deploy key для доступа к репозиторию TasK.

```bash
# Создать пользователя без shell, с домашней директорией
sudo useradd -r -m -d /home/wwwtask -s /sbin/nologin wwwtask

# Добавить в subuid/subgid для rootless podman
echo "wwwtask:165536:65536" | sudo tee -a /etc/subuid /etc/subgid

# Включить lingering, чтобы был XDG_RUNTIME_DIR
sudo loginctl enable-linger wwwtask

# Проверить
loginctl show-user wwwtask | egrep 'UID=|Linger='
ls -ld /run/user/$(id -u wwwtask)

```

## Подготовка SSH deploy key

Сгенерируйте ключ и настройте доступ к приватному репозиторию:

```bash
# Создать каталог под ключи и закрыть права
sudo -u wwwtask mkdir -p /home/wwwtask/.ssh
sudo -u wwwtask chmod 700 /home/wwwtask/.ssh

# Сгенерировать ключ (read-only доступ к репозиторию)
sudo -u wwwtask ssh-keygen -t ed25519 -C "task-worker-deploy" -f /home/wwwtask/.ssh/task_deploy_key

# Добавить GitHub в known_hosts
sudo -u wwwtask sh -c 'ssh-keyscan github.com >> /home/wwwtask/.ssh/known_hosts'

# Вывести публичный ключ и добавить его в Deploy keys репозитория (Read-only)
sudo -u wwwtask cat /home/wwwtask/.ssh/task_deploy_key.pub

# Настроить явное использование ключа для github.com
sudo -u wwwtask bash -lc 'cat > /home/wwwtask/.ssh/config <<EOF
Host github.com
  HostName github.com
  User git
  IdentityFile /home/wwwtask/.ssh/task_deploy_key
  IdentitiesOnly yes
EOF
chmod 600 /home/wwwtask/.ssh/config'

# (Опционально) проверить подключение
sudo -u wwwtask ssh -T git@github.com
```
