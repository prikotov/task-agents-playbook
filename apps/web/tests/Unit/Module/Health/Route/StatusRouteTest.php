<?php

declare(strict_types=1);

namespace Web\Test\Unit\Module\Health\Route;

use PHPUnit\Framework\TestCase;
use Web\Module\Health\Route\StatusRoute;

final class StatusRouteTest extends TestCase
{
    private StatusRoute $route;

    protected function setUp(): void
    {
        $this->route = new StatusRoute();
    }

    public function testStatusPathConstant(): void
    {
        $this->assertSame('/status', StatusRoute::STATUS_PATH);
    }

    public function testStatusConstant(): void
    {
        $this->assertSame('status', StatusRoute::STATUS);
    }

    public function testStatusMethodReturnsPath(): void
    {
        $this->assertSame('/status', $this->route->status());
    }
}
