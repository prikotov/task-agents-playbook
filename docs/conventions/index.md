# Содержание

## Принципы и Стандарты
- [Ценности](principles/values.md)
- [Стиль кода (Code Style)](principles/code_style.md)
- [Правила написания документации](doc-writing-rules.md)

## Базовые паттерны (Core Patterns)
- [Компонент (Component)](core_patterns/component.md)
- [Список (List)](core_patterns/list.md)
- [Перечисление (Enum)](core_patterns/enum.md)
- [Исключение (Exception)](core_patterns/exception.md)
- [Внешний сервис (External Service)](core_patterns/external-service.md)
- [Враппер (Wrapper)](core_patterns/wrapper.md)
- [Фабрика (Factory)](core_patterns/factory.md)
- [Хелпер (Helper)](core_patterns/helper.md)
- [Маппер (Mapper)](core_patterns/mapper.md)
- [Map-класс (Map)](core_patterns/map.md)
- [Сервис (Service)](core_patterns/service.md)
- [Объект передачи данных (DTO)](core_patterns/dto.md)
- [Трейт (Trait)](core_patterns/trait.md)
- [Объект-Значение (Value Object)](core_patterns/value-object.md)

## Тестирование
- [Testing](testing/index.md)

## Конфигурация
- [Конфигурация в Symfony](configuration/configuration.md)

## Модульная архитектура
- [Структура папок на Symfony](symfony-folder-structure.md)
- [Приложения на фреймворке Symfony](symfony-applications.md)
- [Модули](modules/index.md)
- [Конфигурирование модулей](modules/configuration.md)

## Операционные практики
- [Fixes](ops/fixes.md)

## Слои Архитектуры

- [Взаимодействие слоёв (Layer Interaction)](layers/layers.md)
- [Слой Приложения (Application)](layers/application.md)
    - [Сценарий использования (Use Case)](layers/application/use_case.md)
    - [Обработчик Команд (Command Handler)](layers/application/command_handler.md)
    - [Обработчик Запросов (Query Handler)](layers/application/query_handler.md)
    - [Событие (Event)](layers/application/event.md)
- [Слой Домена (Domain)](layers/domain.md)
    - [Сущность (Entity)](layers/domain/entity.md)
    - [Критерий (Criteria)](layers/domain/criteria.md)
    - [Репозиторий (Repository)](layers/domain/repository.md)
    - [Спецификация (Specification)](layers/domain/specification.md)
    - [Калькулятор (Calculator)](layers/domain/calculator.md)
- [Слой Инфраструктуры (Infrastructure)](layers/infrastructure.md)
    - [CriteriaMapper](layers/infrastructure/criteria-mapper.md)
    - [Репозиторий (Repository)](layers/infrastructure/repository.md)
- [Слой интеграций (Integration)](layers/integration.md)
    - [Слушатель Событий (Event Listener)](layers/integration/listener.md)
- [Слой Представления (Presentation)](layers/presentation.md)
    - [Авторизация (Authorization)](layers/presentation/authorization.md)
    - [Консольная команда (Console Command)](layers/presentation/console_command.md)
    - [Контроллер (Controller)](layers/presentation/controller.md)
    - [Формы (Forms)](layers/presentation/forms.md)
    - [Грант (Grant)](layers/presentation/grant.md)
    - [Контроллер списка (List Controller)](layers/presentation/list-controller.md)
    - [Перечисление разрешений (Permission Enum)](layers/presentation/permission_enum.md)
    - [Маршруты (Route)](layers/presentation/route.md)
    - [Правило (Rule)](layers/presentation/rule.md)
    - [Представление (View)](layers/presentation/view.md)
    - [Голосователь (Voter)](layers/presentation/voter.md)
