<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\Enum;

/**
 * Категория сервиса для классификации в мониторинге.
 */
enum ServiceCategoryEnum: string
{
    case infrastructure = 'infrastructure';  // PostgreSQL, RabbitMQ, MinIO
    case llm = 'llm';                        // LLM providers: Ollama, OpenAI, etc.
    case externalApi = 'external_api';       // T-Bank, Email/SMTP
    case cliTool = 'cli_tool';               // yt-dlp, whisper.cpp, djvu, pdf
}
