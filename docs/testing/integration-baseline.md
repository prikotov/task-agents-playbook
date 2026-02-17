# Integration Tests Baseline

**Дата выполнения:** 2026-01-09  
**Команда запуска:** `APP_ENV=test bin/phpunit -c phpunit.xml.dist --testsuite integration --exclude-group whisper --no-progress`

## Summary по падениям

| Метрика | Количество |
|---------|-----------|
| Всего запущено тестов | 721 |
| Assertions | 577 |
| Errors | 545 |
| Failures | 20 |
| **Всего failing tests** | **565** |

## Распределение по приложениям/модулям

| Модуль/Приложение | Количество тестов | Примечания |
|-------------------|------------------|-----------|
| Common (Component, Infrastructure, Routing, Translation) | ~60 | Общие компоненты и инфраструктура |
| Module/Attribution | ~86 | Attribution модуль (Application, Domain, Infrastructure, Integration) |
| Module/Billing | ~15 | Billing модуль |
| Module/Llm | ~5 | LLM модуль |
| Module/Notification | ~1 | Notification модуль |
| Module/Source | ~40 | Source модуль |
| Module/SpeechToText | ~4 | SpeechToText модуль |
| Module/User | ~15 | User модуль |

## Top-5 кластеров падений

### 1. Database Connection Errors (545 errors)
**Количество тестов:** ~545  
**Описание:** Основная проблема - отсутствие подключения к базе данных `database`. Ошибка: `SQLSTATE[08006] [7] could not translate host name "database" to address: Temporary failure in name resolution`

**Примеры тестов:**
- `UpsertFirstTouchConcurrencyTest::testConcurrentRequestsCreateSingleSession`
- `UpsertFirstTouchEntityManagerTest::testEntityManagerRemainsUsableAfterUpsertConflict`
- `AttributionSessionRepositoryTest::*`
- Все тесты, требующие подключения к PostgreSQL

**Причина:** Контейнеры Docker/Podman не запущены или недоступны. Тесты запускались локально без контейнерной инфраструктуры.

### 2. Web Application Tests (SignInController)
**Количество тестов:** ~5  
**Описание:** Тесты контроллеров Web приложения падают из-за отсутствия подключения к базе данных

**Примеры тестов:**
- `Web\Test\Integration\Module\User\Controller\Auth\SignInControllerTest::testSuccessfulLoginRedirectsToDashboard`

### 3. Source Module Tests
**Количество тестов:** ~10  
**Описание:** Тесты модуля Source (downloaders, extractors, file storage)

**Примеры тестов:**
- `SourceFileManagerServiceTest`
- `GithubDownloaderServiceTest`
- `AudioDownloaderServiceTest`
- `FileWorkspaceServiceTest`

### 4. User Module Tests
**Количество тестов:** ~8  
**Описание:** Тесты модуля User (repositories, controllers, services)

**Примеры тестов:**
- `UserRepositoryTest`
- `InviteExternalMemberFlowTest`
- `RegisterCommandHandlerTest`

### 5. Attribution Module Tests
**Количество тестов:** ~7  
**Описание:** Тесты модуля Attribution (concurrency, entity manager)

**Примеры тестов:**
- `UpsertFirstTouchConcurrencyTest`
- `UpsertFirstTouchEntityManagerTest`
- `AttributionSessionRepositoryTest`

## Примечания

1. **Критическая проблема:** 545 из 565 падений (96.5%) вызваны отсутствием подключения к базе данных. Это инфраструктурная проблема, а не проблема с кодом тестов.

2. **Окружение:** Тесты запускались локально без контейнерной инфраструктуры Docker/Podman. Для корректного запуска интеграционных тестов необходимо:
   - Запустить контейнеры через `docker compose -p task up -d --profile test`
   - Или использовать `make tests-integration` (который автоматически запускает контейнеры)

3. **Whisper тесты:** Тесты группы `whisper` были исключены из запуска (`--exclude-group whisper`), так как они требуют реальных вызовов Whisper и являются медленными.

4. **Следующие шаги:**
   - Настроить контейнерную инфраструктуру для запуска тестов
   - После исправления проблемы с базой данных повторно запустить тесты
   - Создать обновлённый baseline с реальными результатами

## Рекомендации

1. **Приоритет 1:** Настроить окружение для запуска интеграционных тестов (Docker/Podman контейнеры)
2. **Приоритет 2:** Повторно запустить тесты после настройки окружения
3. **Приоритет 3:** Анализировать только реальные ошибки тестов, а не инфраструктурные проблемы
