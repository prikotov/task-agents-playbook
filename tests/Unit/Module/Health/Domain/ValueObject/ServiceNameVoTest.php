<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Domain\ValueObject;

use Common\Module\Health\Domain\ValueObject\ServiceNameVo;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Common\Module\Health\Domain\ValueObject\ServiceNameVo
 */
final class ServiceNameVoTest extends TestCase
{
    public function testConstructorAcceptsValidName(): void
    {
        $name = new ServiceNameVo('postgresql');

        self::assertSame('postgresql', $name->value);
        self::assertSame('postgresql', (string) $name);
    }

    public function testConstructorAcceptsMaxLengthName(): void
    {
        $longName = str_repeat('a', 255);
        $name = new ServiceNameVo($longName);

        self::assertSame($longName, $name->value);
    }

    public function testConstructorAcceptsMinLengthName(): void
    {
        $name = new ServiceNameVo('x');

        self::assertSame('x', $name->value);
    }

    public function testConstructorThrowsExceptionForEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service name must be between 1 and 255 characters, got 0.');

        new ServiceNameVo('');
    }

    public function testConstructorThrowsExceptionForTooLongName(): void
    {
        $tooLongName = str_repeat('a', 256);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service name must be between 1 and 255 characters, got 256.');

        new ServiceNameVo($tooLongName);
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $name1 = new ServiceNameVo('postgresql');
        $name2 = new ServiceNameVo('postgresql');

        self::assertTrue($name1->equals($name2));
        self::assertTrue($name2->equals($name1));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $name1 = new ServiceNameVo('postgresql');
        $name2 = new ServiceNameVo('mysql');

        self::assertFalse($name1->equals($name2));
        self::assertFalse($name2->equals($name1));
    }

    public function testToStringReturnsValue(): void
    {
        $name = new ServiceNameVo('rabbitmq');

        self::assertSame('rabbitmq', (string) $name);
    }
}
