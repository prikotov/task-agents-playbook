# Trafilatura CLI

Инструкция по установке и подключению Trafilatura при первом деплое.
https://trafilatura.readthedocs.io/en/latest/

## Требования

- Python 3.10+ (рекомендуем 3.11).
- Утилита `pip` и модуль `venv`.
- Системные заголовки для построения зависимостей (обычно `build-essential` или аналог).

## Установка

1. Установите системные пакеты (AlmaLinux/Fedora/RHEL):
   ```bash
   sudo dnf install -y python3 python3-pip python3-virtualenv gcc gcc-c++ make
   ```

2. Создайте виртуальное окружение, чтобы изолировать Python-зависимости:

   ```bash
   sudo mkdir -p /opt/trafilatura
   sudo chown -R wwwtask:wwwtask /opt/trafilatura
   python3 -m venv /opt/trafilatura
   #source /opt/trafilatura/bin/activate
   #pip install --upgrade pip
   /opt/trafilatura/bin/pip install --upgrade pip
   ```

3. Установите Trafilatura:

   ```bash
   #pip install trafilatura
   /opt/trafilatura/bin/pip install trafilatura
   ```

   > При необходимости включите дополнительные компоненты (
   > `pip install "trafilatura[all]"`), чтобы использовать расширенные фильтры или идентификацию языка.

4. Проверьте установку:

   ```bash
   /opt/trafilatura/bin/trafilatura --version
   ```

5. Зафиксируйте путь до бинаря в `.env.local` (или используйте secrets):

   ```dotenv
   TRAFILATURA_BIN_PATH=/opt/trafilatura/bin/trafilatura
   ```

6. Перезапустите PHP/Symfony сервис (`docker compose restart php-fpm` или аналог), чтобы окружение подхватило переменную.

## Обновление

1. При необходимости обновите сам `pip`:
   ```bash
   sudo /opt/trafilatura/bin/pip install --upgrade pip
   ```
2. Обновите `trafilatura`:
   ```bash
   sudo /opt/trafilatura/bin/pip install --upgrade trafilatura
   ```
3. Проверьте версию `/opt/trafilatura/bin/trafilatura --version` и перезапустите PHP/Symfony сервис, чтобы окружение
   прочитало свежую версию.

## Диагностика

- Логи конвертера пишутся в канал `trafilatura` (Monolog). Проверьте `var/log/prod.log` и `var/log/dev.log` при ошибках.
- Частые причины отказа:
    - `TRAFILATURA_BIN_PATH` указан неверно или бинарь не исполняемый.
    - Отсутствует Python 3.10+ или системные библиотеки.
    - Доступ запрещён к файлу (права на `/opt/trafilatura/bin/trafilatura`).
        - Если окружение создавалось через `sudo`, выполните `sudo chown -R <user>:<group> /opt/trafilatura` перед
          установкой или запускайте `pip` через `sudo /opt/trafilatura/bin/python3 -m pip ...`.

  В этом случае перепроверьте путь, права и установку, повторно активируйте окружение и убедитесь, что бинарь
  запускается вручную.
