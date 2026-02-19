<?php

declare(strict_types=1);

namespace Web\Test\E2E\Module\Health;

use PHPUnit\Framework\Attributes\Group;
use Web\Test\E2E\Base\PantherWebTestCase;

/**
 * E2E тест для публичной статус-страницы.
 *
 * Проверяет доступность и отображение страницы статуса.
 */
#[Group('smoke')]
#[Group('e2e-web')]
final class StatusPageTest extends PantherWebTestCase
{
    private const string STATUS_PATH = '/status';

    /**
     * Проверяет, что страница статуса доступна без авторизации.
     */
    public function testStatusPageIsAccessible(): void
    {
        $client = self::createPantherClient();

        $client->request('GET', self::STATUS_PATH);

        $this->assertSelectorIsVisible('html body');
    }

    /**
     * Проверяет наличие заголовка страницы.
     */
    public function testStatusPageHasTitle(): void
    {
        $client = self::createPantherClient();

        $client->request('GET', self::STATUS_PATH);

        $this->assertSelectorExists('h1');
    }

    /**
     * Проверяет корректность базовой HTML структуры страницы.
     */
    public function testPageHasBasicHtmlStructure(): void
    {
        $client = self::createPantherClient();

        $client->request('GET', self::STATUS_PATH);

        $this->assertSelectorExists('html');
        $this->assertSelectorExists('head');
        $this->assertSelectorExists('body');
    }
}
