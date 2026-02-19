# TASK-status-cli-console: Console команда для CLI Tools Health Checks

## Метаданные
- **Тип**: feat
- **Дата создания**: 2026-02-14
- **Ценность**: V3
- **Сложность**: C2
- **Приоритет**: P1
- **Зависит от**: TASK-status-cli-ytdlp, TASK-status-cli-whisper, TASK-status-cli-djvu, TASK-status-cli-pdf
- **Epic**: [EPIC-status-page](EPIC-status-page.todo.md)
- **Автор**: backend_developer
- **Исполнитель**: backend_developer
- **Ветка**: task/TASK-status-cli-console
- **PR**: #2114
- **Статус**: done

## 1. Концепция и Цель
### Story (User Story)
Как девопс,
я хочу запускать health checks CLI инструментов через cron на Worker Server,
 чтобы результаты сохранялись в БД и были доступны с Web Server.

### Цель (SMART)
Реализовать Console команду для запуска health checks CLI инструментов:
1) Одна команда с аргументом serviceName;
2) Выполняет проверку через HealthCheckerRegistryService;
3) Сохраняет результат в service_status таблицу;
4) Используется в cron на Worker Server.

## 2. Контекст и Границы (Scope)
**Где делаем:** `apps/console/src/Command/Health/`

**Архитектурный контекст:** 
- CLI tools установлены только на Worker Server
- Web Server читает статусы из БД (Cron+DB подход из ADR-001)
- Команда запускается каждую минуту для каждого сервиса

**Границы (Out of Scope):**
- HTTP API для health checks (уже есть /health/ready)
- Автоматическая настройка crontab
- Параллельное выполнение всех проверок в одной команде

## 3. Требования (MoSCoW)
### 🔴 Must Have
- [x] Command `health:check:cli-tool` с аргументом `serviceName`
- [x] Поддержка сервисов: yt-dlp, whisper, djvu, pdf
- [x] Интеграция с HealthCheckerRegistryService
- [x] Запись результата в ServiceStatusRepository
- [x] Обработка ошибок с ненулевым exit code
- [x] Unit тест

### 🟡 Should Have
- [x] Опция `--dry-run` для проверки без записи в БД
- [x] Логирование в stdout/stderr
- [x] Опция `--all` для проверки всех сервисов

### 🟢 Could Have
- [x] Опция `--json` для machine-readable вывода
- [x] Метрики времени выполнения (responseTimeMs в результате)

### ⚫ Won't Have
- [ ] Параллельное выполнение проверок
- [ ] Автоматическая установка cron

## 4. План реализации (Tasks)
1. [x] Создать `HealthCheckCliToolCommand.php` в apps/console/src/Command/Health/
2. [x] Добавить аргумент serviceName с валидацией (yt-dlp, whisper, djvu, pdf)
3. [x] Инжектировать HealthCheckerRegistryService и ServiceStatusRepository
4. [x] Реализовать маппинг serviceName → checker service
5. [x] Реализовать запись результата в БД
6. [x] Добавить конфигурацию в services.yaml (autoconfigure)
7. [x] Написать Unit тест
8. [x] Документировать crontab setup (в --help)

## 5. Критерии приемки (Definition of Ready)
- [x] Команда успешно выполняется для каждого сервиса
- [x] Результат сохраняется в БД
- [x] При ошибке проверки команда возвращает ненулевой exit code
- [x] Unit тест проходит
- [x] Зарегистрирована в DI контейнере (через autoconfigure)

## 6. Самопроверка (Verification)
```bash
# Ручная проверка
bin/console health:check:cli-tool yt-dlp
bin/console health:check:cli-tool whisper
bin/console health:check:cli-tool djvu
bin/console health:check:cli-tool pdf

# Тесты
make tests-unit
make check
```

## 7. Риски и Зависимости
- Зависит от реализации Integration Services для всех CLI tools
- Требует наличия service_status таблицы

## 8. Источники
- [ ] [ADR-001 в EPIC-status-page](EPIC-status-page.todo.md#adr-001-cli-tools-health-checks--integration-слой-через-querybus--crondb)
- [ ] [Console Command Best Practices](https://symfony.com/doc/current/console.html)

## 9. Комментарии

### Пример crontab на Worker Server
```bash
# /etc/cron.d/task-health-checks
* * * * * task cd /var/www/task && bin/console health:check:cli-tool yt-dlp >> /var/log/task/health-ytdlp.log 2>&1
* * * * * task cd /var/www/task && bin/console health:check:cli-tool whisper >> /var/log/task/health-whisper.log 2>&1
* * * * * task cd /var/www/task && bin/console health:check:cli-tool djvu >> /var/log/task/health-djvu.log 2>&1
* * * * * task cd /var/www/task && bin/console health:check:cli-tool pdf >> /var/log/task/health-pdf.log 2>&1
```

### Маппинг serviceName → checker
| serviceName | Checker Service |
|-------------|-----------------|
| yt-dlp | CheckYtDlpHealthServiceInterface |
| whisper | CheckWhisperHealthServiceInterface |
| djvu | CheckDjvuHealthServiceInterface |
| pdf | CheckPdfHealthServiceInterface |

## История изменений
| Дата | Автор | Изменение |
| :--- | :--- | :--- |
| 2026-02-14 | backend_developer | Создание задачи (ADR-001) |
| 2026-02-15 | backend_developer | Реализация: HealthCheckCliToolCommand с опциями --all, --dry-run, --json, Unit тесты |
