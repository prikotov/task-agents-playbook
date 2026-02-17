#!/bin/bash

# Скрипт быстрой настройки сервера для воркеров TasK
# Запускать с правами root или через sudo
# Поддерживается только AlmaLinux 10+

set -e

echo "=== Настройка сервера для воркеров TasK ==="

# Проверка, что скрипт запущен от root
if [[ $EUID -ne 0 ]]; then
   echo "Этот скрипт должен быть запущен от root (используйте sudo)"
   exit 1
fi

# Проверка дистрибутива
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$NAME
    VER=$VERSION_ID
else
    echo "Не удалось определить дистрибутив Linux"
    exit 1
fi

if [[ $OS != *"AlmaLinux"* ]] || [[ ${VER%%.*} -lt 10 ]]; then
    echo "Этот скрипт поддерживает только AlmaLinux 10+"
    echo "Обнаружен дистрибутив: $OS $VER"
    exit 1
fi

echo "Обнаружен дистрибутив: $OS $VER"

# Установка базовых пакетов
echo "=== Установка базовых пакетов ==="
dnf install -y gcc gcc-c++ make cmake git python3 python3-pip python3-virtualenv ffmpeg curl
dnf install -y php84-cli php84-json php84-mbstring \
    php84-xml php84-pdo php84-pgsql php84-amqp php84-curl php84-zip \
    php84-gd php84-intl php84-opcache
dnf install -y poppler-utils djvulibre

# Установка Composer
echo "=== Установка Composer ==="
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Создание пользователя wwwtask
echo "=== Создание пользователя wwwtask ==="
useradd -m -s /bin/bash wwwtask || true

# Создание директорий
echo "=== Создание директорий ==="
install -d -m 755 -o wwwtask -g wwwtask /var/www/task
install -d -m 755 -o wwwtask -g wwwtask /var/www/task/releases
install -d -m 755 -o wwwtask -g wwwtask /var/www/task/shared
install -d -m 755 -o wwwtask -g wwwtask /var/www/task/shared/var
install -d -m 755 -o wwwtask -g wwwtask /var/www/task/shared/var/log
install -d -m 755 -o wwwtask -g wwwtask /var/log/task/messenger

# Установка yt-dlp
echo "=== Установка yt-dlp ==="
mkdir -p /opt/yt-dlp
python3 -m venv /opt/yt-dlp
/opt/yt-dlp/bin/pip install --upgrade pip
/opt/yt-dlp/bin/pip install --no-cache-dir yt-dlp
ln -sf /opt/yt-dlp/bin/yt-dlp /usr/local/bin/yt-dlp

# Установка whisper.cpp
echo "=== Установка whisper.cpp ==="
git clone https://github.com/ggerganov/whisper.cpp.git /opt/whisper.cpp
cd /opt/whisper.cpp
WHISPER_CPP_VERSION=$(git describe --tags --abbrev=0)
echo "Installing whisper.cpp version: ${WHISPER_CPP_VERSION}"
git checkout ${WHISPER_CPP_VERSION}
cmake -B build
cmake --build build -j --config Release
./models/download-ggml-model.sh large-v3
ln -sf /opt/whisper.cpp/build/bin/whisper-cli /usr/local/bin/whisper-cli
ln -sf /opt/whisper.cpp/build/bin/whisper-server /usr/local/bin/whisper-server

# Установка диаризации
echo "=== Установка диаризации (diarize.py) ==="
mkdir -p /opt/diarize-workdir
python3 -m venv /opt/diarize
/opt/diarize/bin/pip install --upgrade pip
/opt/diarize/bin/pip install --no-cache-dir "huggingface_hub==0.35.3" torch pyannote-audio soundfile speechbrain torchaudio "requests[socks]"

# Создание wrapper script для diarize.py
echo '#!/bin/bash' > /opt/diarize-workdir/diarize.sh
echo 'cd /opt/diarize-workdir' >> /opt/diarize-workdir/diarize.sh
echo '/opt/diarize/bin/python "$@"' >> /opt/diarize-workdir/diarize.sh
chmod +x /opt/diarize-workdir/diarize.sh
ln -sf /opt/diarize-workdir/diarize.sh /usr/local/bin/diarize

# Установка Trafilatura
echo "=== Установка Trafilatura ==="
mkdir -p /opt/trafilatura
python3 -m venv /opt/trafilatura
/opt/trafilatura/bin/pip install --upgrade pip
/opt/trafilatura/bin/pip install trafilatura

# Установка Docling
echo "=== Установка Docling ==="
mkdir -p /opt/docling
python3 -m venv /opt/docling
/opt/docling/bin/pip install --upgrade pip
/opt/docling/bin/pip install docling --extra-index-url https://download.pytorch.org/whl/cpu

# Установка MinerU
echo "=== Установка MinerU ==="
mkdir -p /opt/mineru
python3 -m venv /opt/mineru
/opt/mineru/bin/pip install --upgrade pip
/opt/mineru/bin/pip install "mineru[core]" --extra-index-url https://download.pytorch.org/whl/cpu

# Установка Ollama
echo "=== Установка Ollama ==="
curl -fsSL https://ollama.com/install.sh | sh
systemctl enable --now ollama

# Настройка RabbitMQ
echo "=== Настройка RabbitMQ ==="
rabbitmqctl add_vhost task || true
rabbitmqctl add_user task ChangeMe! || true
rabbitmqctl set_permissions -p task task ".*" ".*" ".*" || true

# Создание очередей для воркеров
echo "=== Создание очередей для воркеров ==="
rabbitmqadmin declare queue --vhost=task name=source_download durable=true || true
rabbitmqadmin declare queue --vhost=task name=source_extract durable=true || true
rabbitmqadmin declare queue --vhost=task name=source_convert durable=true || true
rabbitmqadmin declare queue --vhost=task name=source_diarize durable=true || true
rabbitmqadmin declare queue --vhost=task name=source_transcribe durable=true || true
rabbitmqadmin declare queue --vhost=task name=source_make_document durable=true || true
rabbitmqadmin declare queue --vhost=task name=source_make_document_chunks durable=true || true
rabbitmqadmin declare queue --vhost=task name=source_events durable=true || true

# Настройка файрвола
echo "=== Настройка файрвола ==="
if command -v firewall-cmd &> /dev/null; then
    firewall-cmd --add-port=5672/tcp --permanent
    firewall-cmd --add-port=15672/tcp --permanent
    firewall-cmd --reload
elif command -v ufw &> /dev/null; then
    ufw allow 5672/tcp
    ufw allow 15672/tcp
fi

echo ""
echo "=== Установка завершена! ==="
echo ""
echo "Далее выполните следующие шаги:"
echo "1. Создайте новую версию приложения:"
echo "   VERSION=\$(date +%Y-%m-%d)-v1.2.3"
echo "   sudo -u wwwtask mkdir -p /var/www/task/releases/task-\$VERSION"
echo "   sudo -u wwwtask git clone https://github.com/prikotov/TasK.git /var/www/task"
echo "2. Установите зависимости: sudo -u wwwtask make install"
echo "3. Настройте переменные окружения в `.env.local` (или `.env.prod.local`) в корне проекта на worker server"
echo "   (пример базового набора переменных — в `.env` репозитория)."
echo "4. Настройте diarize.py (см. docs/user/deploy/setup-worker-production/diarize.md)"
echo "5. Настройте Supervisor для воркеров (см. docs/user/deploy/setup-worker-production/supervisor-services.md)"
echo "6. Скачайте модели Ollama: sudo -u wwwtask ollama pull jeffh/intfloat-multilingual-e5-large-instruct:q8_0"
echo "7. Проверьте созданные очереди: rabbitmqctl list_queues -p task"
echo "8. Убедитесь, что сервер RabbitMQ доступен по сети"
echo ""
echo "Подробная инструкция в docs/user/deploy/setup-worker-production/README.md"
