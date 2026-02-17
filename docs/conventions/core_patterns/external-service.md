# Правила работы с внешним сервисом (External Service)

**Работа с внешним сервисом** — это взаимодействие с API, SDK, облачным сервисом или другим инфраструктурным ресурсом.

## Общие правила

- ❗ **Запрещено выполнять запросы к внешним сервисам внутри транзакций БД.**  
  Запрос может занять значительное время → блокируются строки/таблицы, растёт число php-fpm процессов, возможны дедлоки.

- **Ключи, секреты:**
    - ❌ Запрещено хардкодить в коде.
    - ✅ Определяются в `.env.local`, прокидываются в параметры и передаются в сервисы через DI с помощью `services.yaml`.

- **Таймауты обязательны.**
  Минимально: `timeout`. Рекомендуется также `max_duration`.

- **Все запросы и ответы логируются.**
    - `info` — факт отправки (метод, URL, параметры); успешные ответы (статус, тело).
    - `error` — ошибки (код ответа, тело ответа, исключение).
    - Логи должны быть структурированными (контекст в виде массива), а не строками.

- **Исключения оборачиваются в инфраструктурные.**  
  Все исключения `HttpExceptionInterface`, `TransportExceptionInterface`, `DecodingExceptionInterface` и SDK исключения
  должны оборачиваться в `InfrastructureException` (или производные).  
  В сообщение стоит добавлять тело ответа и накопленные данные (например, буфер при stream).

## Пример конфигурации

```yaml
parameters:
    module.llm.deepseek.base_url: '%env(string:DEEPSEEK_BASE_URL)%'
    module.llm.deepseek.api_key: '%env(string:DEEPSEEK_API_KEY)%'
    module.llm.deepseek.proxy: '%env(default::DEEPSEEK_PROXY)%'

    module.llm.fireworks.base_url: '%env(string:FIREWORKS_BASE_URL)%'
    module.llm.fireworks.api_key: '%env(string:FIREWORKS_API_KEY)%'
    module.llm.fireworks.proxy: '%env(default::FIREWORKS_PROXY)%'
    
    # Таймауты и общие HTTP-опции
    module.llm.deepseek.http.timeout: 3
    module.llm.deepseek.http.max_duration: 300

    module.llm.fireworks.http.timeout: 3
    module.llm.fireworks.http.max_duration: 300

services:
    # Преднастроенный клиент для DeepSeek
    http_client.deepseek:
        factory: [ '@http_client', 'withOptions' ]
        arguments:
            - {
                timeout: '%module.llm.deepseek.http.timeout%',
                max_duration: '%module.llm.deepseek.http.max_duration%',
                proxy: '%module.llm.deepseek.proxy%',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    Authorization: 'Bearer %module.llm.deepseek.api_key%'
                }
            }

    http_client.fireworks:
        factory: [ '@http_client', 'withOptions' ]
        arguments:
            - {
                timeout: '%module.llm.fireworks.http.timeout%',
                max_duration: '%module.llm.fireworks.http.max_duration%',
                proxy: '%module.llm.fireworks.proxy%',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    Authorization: 'Bearer %module.llm.fireworks.api_key%'
                }
            }

    Common\Module\Llm\Infrastructure\Component\DeepSeek\DeepSeekComponent:
        arguments:
            $httpClient: '@http_client.deepseek'
            $baseUrl: '%module.llm.deepseek.base_url%'
            $logger: '@monolog.logger.deepseek'

    Common\Module\Llm\Infrastructure\Component\Fireworks\FireworksComponent:
        arguments:
            $httpClient: '@http_client.fireworks'
            $baseUrl: '%module.llm.fireworks.base_url%'
            $logger: '@monolog.logger.fireworks'
```

## Пример реализации

```php
<?php

declare(strict_types=1);

namespace Common\Module\Llm\Infrastructure\Component\DeepSeek;

use Common\Exception\InfrastructureException;
use Common\Helper\JsonHelper;
use Common\Module\Llm\Infrastructure\Component\DeepSeek\Dto\CompletionsRequestDto;
use Common\Module\Llm\Infrastructure\Component\DeepSeek\Dto\CompletionsResponseDto;
use Common\Module\Llm\Infrastructure\Component\DeepSeek\Mapper\CompletionsRequestMapper;
use Common\Module\Llm\Infrastructure\Component\DeepSeek\Mapper\CompletionsResponseMapper;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class DeepSeekComponent implements DeepSeekComponentInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
        private CompletionsRequestMapper $completionsRequestMapper,
        private CompletionsResponseMapper $completionsResponseMapper,
        private LoggerInterface $logger,
    ) {}

    /**
     * @link https://api-docs.deepseek.com/api/create-chat-completion
     */
    #[Override]
    public function completions(CompletionsRequestDto $requestDto): CompletionsResponseDto
    {
        $url = rtrim($this->baseUrl, '/') . '/chat/completions';
        $params = $this->completionsRequestMapper->map($requestDto);

        $this->logger->info('DeepSeek request started', [
            'url' => $url,
            'params' => $params,
        ]);

        $response = null;
        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => $params,
            ]);
            $data = $response->toArray();
            $this->logger->info('DeepSeek response', ['data' => $data]);
            return $this->completionsResponseMapper->map($data);
        } catch (
            HttpExceptionInterface |
            TransportExceptionInterface |
            DecodingExceptionInterface $e
        ) {
            $responseBody = $response?->getContent(false);

            $this->logger->error('DeepSeek request failed', [
                'exception' => $e->getMessage(),
                'response' => $responseBody,
            ]);

            throw new InfrastructureException(
                message: $e->getMessage() . ($responseBody !== null ? ' body: ' . $responseBody : ''),
                previous: $e
            );
        }

        $this->logger->info('DeepSeek request finished');
    }
}
```
