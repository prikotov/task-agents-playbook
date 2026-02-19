<?php

declare(strict_types=1);

namespace Web\Module\Health\Route;

final readonly class StatusRoute
{
    public const string STATUS_PATH = '/status';
    public const string STATUS = 'status';

    public function status(): string
    {
        return self::STATUS_PATH;
    }
}
