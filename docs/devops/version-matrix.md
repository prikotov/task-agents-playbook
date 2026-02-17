# Матрица версий ПО и идентичность окружений

Этот документ фиксирует версии используемых пакетов, библиотек, системного ПО и ОС для обеспечения максимальной надежности и минимизации ошибок, связанных с различиями в окружениях (Dev/Staging/Production).

## Принцип идентичности (Environment Parity)
Мы стремимся к тому, чтобы окружение разработки было максимально приближено к продуктовому. Все изменения версий должны сначала тестироваться локально, затем на Staging и только после этого попадать в Production.

## Системное ПО и ОС

| Компонент | Версия (Target) | Контейнер/Сервис | Примечание |
| :--- | :--- | :--- | :--- |
| ОС (Base Image) | Debian Bookworm | `php`, `worker-cli` | |
| PHP | 8.4.x | `php`, `worker-cli` | |
| PostgreSQL | 16.x | `database` | + pgvector |
| Nginx | 1.25.x | `nginx` | |
| RabbitMQ | 3.13.x | `rabbitmq` | |
| Mercure | 0.16.x | `mercure` | |
| Redis/KeyDB | 7.x | `redis` | |

## Ключевые библиотеки (Backend)

| Библиотека | Версия (Constraint) | Файл |
| :--- | :--- | :--- |
| Symfony | 7.3.x | `composer.json` |
| Doctrine ORM | ^3.0 | `composer.json` |

## Ключевые библиотеки (Frontend)

| Библиотека | Версия (Constraint) | Файл |
| :--- | :--- | :--- |
| Bootstrap | 5.3.x | `importmap.php` / Phoenix theme |
| Stimulus | ^3.2 | `importmap.php` |
| Turbo | ^7.3 | `importmap.php` |

## Инструменты Worker-CLI

| Утилита | Версия | Назначение |
| :--- | :--- | :--- |
| pdfinfo (poppler) | 22.x | Извлечение инфо из PDF |
| MinerU/Docling | Latest Stable | Парсинг документов |

---
*Последнее обновление: 2026-01-21*
