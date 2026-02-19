<?php

declare(strict_types=1);

namespace Common\Test\Unit\Module\Health\Form\Dashboard;

use Common\Module\Health\Application\Enum\ServiceCategoryEnum;
use Common\Module\Health\Application\Enum\ServiceStatusEnum;
use PHPUnit\Framework\TestCase;
use Web\Module\Health\Form\Dashboard\FilterFormModel;

/**
 * @see \Web\Module\Health\Form\Dashboard\FilterFormModel
 */
final class FilterFormModelTest extends TestCase
{
    /**
     * @test
     */
    public function constructorSetsDefaultValues(): void
    {
        $model = new FilterFormModel();

        self::assertNull($model->getCategory());
        self::assertNull($model->getStatus());
    }

    /**
     * @test
     */
    public function constructorSetsProvidedValues(): void
    {
        $model = new FilterFormModel(
            category: ServiceCategoryEnum::infrastructure,
            status: ServiceStatusEnum::operational,
        );

        self::assertSame(ServiceCategoryEnum::infrastructure, $model->getCategory());
        self::assertSame(ServiceStatusEnum::operational, $model->getStatus());
    }

    /**
     * @test
     */
    public function settersUpdateValues(): void
    {
        $model = new FilterFormModel();

        $model->setCategory(ServiceCategoryEnum::llm);
        $model->setStatus(ServiceStatusEnum::degraded);

        self::assertSame(ServiceCategoryEnum::llm, $model->getCategory());
        self::assertSame(ServiceStatusEnum::degraded, $model->getStatus());
    }

    /**
     * @test
     */
    public function toQueryParamsReturnsEmptyArrayWhenNoFilters(): void
    {
        $model = new FilterFormModel();

        $params = $model->toQueryParams();

        self::assertSame([], $params);
    }

    /**
     * @test
     */
    public function toQueryParamsReturnsCategoryOnly(): void
    {
        $model = new FilterFormModel(category: ServiceCategoryEnum::infrastructure);

        $params = $model->toQueryParams();

        self::assertSame(['category' => 'infrastructure'], $params);
    }

    /**
     * @test
     */
    public function toQueryParamsReturnsStatusOnly(): void
    {
        $model = new FilterFormModel(status: ServiceStatusEnum::operational);

        $params = $model->toQueryParams();

        self::assertSame(['status' => 'operational'], $params);
    }

    /**
     * @test
     */
    public function toQueryParamsReturnsBothFilters(): void
    {
        $model = new FilterFormModel(
            category: ServiceCategoryEnum::llm,
            status: ServiceStatusEnum::outage,
        );

        $params = $model->toQueryParams();

        self::assertSame([
            'category' => 'llm',
            'status' => 'outage',
        ], $params);
    }

    /**
     * @test
     */
    public function toQueryParamsWithPrefix(): void
    {
        $model = new FilterFormModel(
            category: ServiceCategoryEnum::cliTool,
            status: ServiceStatusEnum::maintenance,
        );

        $params = $model->toQueryParams('filter');

        self::assertSame([
            'filter[category]' => 'cli_tool',
            'filter[status]' => 'maintenance',
        ], $params);
    }

    /**
     * @test
     */
    public function toQueryParamsWithNullPrefix(): void
    {
        $model = new FilterFormModel(category: ServiceCategoryEnum::externalApi);

        $params = $model->toQueryParams(null);

        self::assertSame(['category' => 'external_api'], $params);
    }

    /**
     * @test
     * @dataProvider allCategoriesProvider
     */
    public function allCategoriesWorkInToQueryParams(ServiceCategoryEnum $category): void
    {
        $model = new FilterFormModel(category: $category);

        $params = $model->toQueryParams();

        self::assertSame($category->value, $params['category']);
    }

    /**
     * @test
     * @dataProvider allStatusesProvider
     */
    public function allStatusesWorkInToQueryParams(ServiceStatusEnum $status): void
    {
        $model = new FilterFormModel(status: $status);

        $params = $model->toQueryParams();

        self::assertSame($status->value, $params['status']);
    }

    /**
     * @return iterable<int, array{0: ServiceCategoryEnum}>
     */
    public static function allCategoriesProvider(): iterable
    {
        yield [ServiceCategoryEnum::infrastructure];
        yield [ServiceCategoryEnum::llm];
        yield [ServiceCategoryEnum::externalApi];
        yield [ServiceCategoryEnum::cliTool];
    }

    /**
     * @return iterable<int, array{0: ServiceStatusEnum}>
     */
    public static function allStatusesProvider(): iterable
    {
        yield [ServiceStatusEnum::operational];
        yield [ServiceStatusEnum::degraded];
        yield [ServiceStatusEnum::outage];
        yield [ServiceStatusEnum::maintenance];
    }
}
