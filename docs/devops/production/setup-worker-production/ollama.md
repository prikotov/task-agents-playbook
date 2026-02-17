# Установка и обновление Ollama

Ollama используется для локального запуска моделей LLM и генерации эмбеддингов.
Инструкция поможет развернуть сервис на production-сервере и подготовить набор
минимально необходимых моделей.

## Установка
```bash
curl -fsSL https://ollama.com/install.sh | sh
```
Скрипт установит бинарный файл и зарегистрирует systemd-сервис `ollama`.

Запустите сервис и добавьте его в автозапуск:
```bash
sudo systemctl enable --now ollama
```

## Безопасность

Сервис Ollama должен быть доступен только локально. По умолчанию он слушает
`127.0.0.1:11434`. Не пробрасывайте этот порт наружу и не меняйте адрес
прослушивания, иначе любой клиент в сети сможет использовать модели.

## Загрузка моделей
Рекомендуемый набор моделей:

### Для эмбеддинга
- `jeffh/intfloat-multilingual-e5-large-instruct:q8_0`
- `bge-m3`

> **Примечание.** Модель `intfloat-multilingual-e5-large-instruct` размещена в репозитории
> `jeffh`, поэтому необходимо указывать полный идентификатор
> `jeffh/intfloat-multilingual-e5-large-instruct:q8_0`.

### Для суммаризации
- `qwen3:4b`
- `llama3.1:8b-instruct` *(опционально, для более сложных задач)*

Скачивание моделей выполняется командой `ollama pull` от имени пользователя,
под которым запускается приложение:
```bash
sudo -u wwwtask ollama pull jeffh/intfloat-multilingual-e5-large-instruct:q8_0
sudo -u wwwtask ollama pull bge-m3
sudo -u wwwtask ollama pull qwen3:4b
sudo -u wwwtask ollama pull llama3.1:8b-instruct
```

Если `sudo` не находит исполняемый файл (`sudo: ollama: command not found`),
укажите полный путь `/usr/local/bin/ollama` или выполните команды из оболочки
пользователя `wwwtask`:
```bash
sudo -u wwwtask /usr/local/bin/ollama pull jeffh/intfloat-multilingual-e5-large-instruct:q8_0
```

## Обновление Ollama
Официальный инсталлятор также выполняет обновление. Просто повторите команду
установки:
```bash
curl -fsSL https://ollama.com/install.sh | sh
sudo systemctl restart ollama
```

## Проверка
```bash
# версия и статус сервиса
ollama --version
systemctl status ollama

# IP/порт (должен быть 127.0.0.1:11434)
sudo ss -tulpn | grep 11434

# список установленных моделей
ollama list

# HTTP-проверка API
curl http://localhost:11434/api/tags
```
Если команды выполняются без ошибок, сервис готов к работе.
