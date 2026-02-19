<?php

declare(strict_types=1);

namespace Common\Module\Health\Domain\ValueObject;

use InvalidArgumentException;

/**
 * ValueObject для имени сервиса в мониторинге.
 */
final readonly class ServiceNameVo
{
    private const MIN_LENGTH = 1;
    private const MAX_LENGTH = 255;

    public function __construct(
        public string $value,
    ) {
        $length = strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Service name must be between %d and %d characters, got %d.',
                    self::MIN_LENGTH,
                    self::MAX_LENGTH,
                    $length,
                ),
            );
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
