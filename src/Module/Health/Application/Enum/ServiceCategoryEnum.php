<?php

declare(strict_types=1);

namespace Common\Module\Health\Application\Enum;

/**
 * Категория сервиса для Application слоя.
 * Используется в DTO для изоляции от Domain слоя.
 */
enum ServiceCategoryEnum: string
{
    case infrastructure = 'infrastructure';
    case llm = 'llm';
    case externalApi = 'external_api';
    case cliTool = 'cli_tool';
}
