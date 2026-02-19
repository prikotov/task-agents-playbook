<?php

declare(strict_types=1);

namespace Web\Test\E2E\Module\Health;

use PHPUnit\Framework\Attributes\Group;
use Web\Test\E2E\Base\PantherWebTestCase;
use Web\Test\Support\Trait\E2eUiLoginTrait;

/**
 * E2E тест для Admin Dashboard мониторинга.
 *
 * Проверяет доступность и функциональность панели мониторинга для администраторов.
 */
#[Group('smoke')]
#[Group('e2e-web')]
final class DashboardTest extends PantherWebTestCase
{
    use E2eUiLoginTrait;

    private const string DASHBOARD_PATH = '/admin/health/dashboard';

    /**
     * Проверяет, что dashboard доступен авторизованному администратору.
     */
    public function testDashboardIsAccessibleForAdmin(): void
    {
        $client = self::createPantherClient();
        $this->loginAdminViaUi($client);

        $client->request('GET', self::DASHBOARD_PATH);

        $this->assertSelectorIsVisible('html body');
    }

    /**
     * Проверяет наличие заголовка страницы.
     */
    public function testDashboardHasTitle(): void
    {
        $client = self::createPantherClient();
        $this->loginAdminViaUi($client);

        $client->request('GET', self::DASHBOARD_PATH);

        $this->assertSelectorExists('h1');
    }

    /**
     * Проверяет наличие формы фильтрации.
     */
    public function testDashboardHasFilterForm(): void
    {
        $client = self::createPantherClient();
        $this->loginAdminViaUi($client);

        $client->request('GET', self::DASHBOARD_PATH);

        $this->assertSelectorExists('form');
    }

    /**
     * Проверяет наличие статистических карточек.
     */
    public function testDashboardHasStatisticsCards(): void
    {
        $client = self::createPantherClient();
        $this->loginAdminViaUi($client);

        $client->request('GET', self::DASHBOARD_PATH);

        // Проверяем наличие 4 карточек статистики (operational, degraded, outage, maintenance)
        $cards = $client->getCrawler()->filter('.card.h-100');
        $this->assertGreaterThanOrEqual(4, $cards->count());
    }

    /**
     * Проверяет корректность базовой HTML структуры страницы.
     */
    public function testPageHasBasicHtmlStructure(): void
    {
        $client = self::createPantherClient();
        $this->loginAdminViaUi($client);

        $client->request('GET', self::DASHBOARD_PATH);

        $this->assertSelectorExists('html');
        $this->assertSelectorExists('head');
        $this->assertSelectorExists('body');
    }
}
