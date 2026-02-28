<?php

declare(strict_types=1);

use App\Services\SeoService;
use PHPUnit\Framework\TestCase;

final class SeoServiceTest extends TestCase
{
    private SeoService $service;
    private array $actions;
    private array $cities;

    protected function setUp(): void
    {
        $this->service = new SeoService([
            'app' => [
                'name' => 'Квадратный метр',
                'url' => 'https://example.test',
            ],
        ]);
        $this->actions = [
            ['id' => 1, 'name' => 'Продажа'],
            ['id' => 2, 'name' => 'Аренда'],
        ];
        $this->cities = [
            ['id' => 1, 'name' => 'Саратов'],
            ['id' => 2, 'name' => 'Энгельс'],
        ];
    }

    public function testWhitelistFiltersAreIndexable(): void
    {
        $seo = $this->service->buildIndexSeo(
            ['city_id' => '1', 'action_id' => '2', 'room' => '3', 'price_min' => '', 'price_max' => '', 'post_id' => ''],
            'date_desc',
            1,
            $this->actions,
            $this->cities,
            ['city_id' => '1', 'action_id' => '2', 'room' => '3']
        );

        $this->assertSame('index,follow', $seo['robots'] ?? '');
        $this->assertSame('https://example.test/?city_id=1&action_id=2&room=3', $seo['canonical'] ?? '');
        $this->assertSame('Главная', $seo['breadcrumbs'][0]['name'] ?? '');
        $this->assertSame('https://example.test/', $seo['breadcrumbs'][0]['url'] ?? '');
    }

    public function testSortForcesNoindexAndCanonicalCleanup(): void
    {
        $seo = $this->service->buildIndexSeo(
            ['city_id' => '1', 'action_id' => '', 'room' => '', 'price_min' => '', 'price_max' => '', 'post_id' => ''],
            'price_desc',
            1,
            $this->actions,
            $this->cities,
            ['city_id' => '1', 'sort' => 'price_desc']
        );

        $this->assertSame('noindex,follow', $seo['robots'] ?? '');
        $this->assertSame('https://example.test/?city_id=1', $seo['canonical'] ?? '');
    }

    public function testPageTwoIsNoindexWithFirstPageCanonical(): void
    {
        $seo = $this->service->buildIndexSeo(
            ['city_id' => '1', 'action_id' => '', 'room' => '', 'price_min' => '', 'price_max' => '', 'post_id' => ''],
            'date_desc',
            2,
            $this->actions,
            $this->cities,
            ['city_id' => '1', 'page' => '2']
        );

        $this->assertSame('noindex,follow', $seo['robots'] ?? '');
        $this->assertSame('https://example.test/?city_id=1', $seo['canonical'] ?? '');
    }

    public function testUnknownQueryForcesNoindex(): void
    {
        $seo = $this->service->buildIndexSeo(
            ['city_id' => '1', 'action_id' => '', 'room' => '', 'price_min' => '', 'price_max' => '', 'post_id' => ''],
            'date_desc',
            1,
            $this->actions,
            $this->cities,
            ['city_id' => '1', 'utm_source' => 'test']
        );

        $this->assertSame('noindex,follow', $seo['robots'] ?? '');
    }

    public function testDetailSeoContainsProductBasics(): void
    {
        $seo = $this->service->buildDetailSeo([
            'id' => 10,
            'title' => '2-комнатная квартира',
            'action_name' => 'Продажа',
            'object_name' => 'Квартира',
            'city_name' => 'Саратов',
            'street' => 'Ленина 1',
            'cost' => 5400000,
            'descr_post' => 'Отличная квартира',
            'user_id' => 1,
        ], []);

        $this->assertSame('index,follow', $seo['robots'] ?? '');
        $this->assertSame('https://example.test/detail/10', $seo['canonical'] ?? '');
        $this->assertSame('product', $seo['og_type'] ?? '');
        $this->assertSame('Главная', $seo['breadcrumbs'][0]['name'] ?? '');
        $this->assertSame('Продажа Квартира', $seo['breadcrumbs'][1]['name'] ?? '');

        $breadcrumbJsonLd = $seo['json_ld'][0] ?? [];
        $this->assertSame('BreadcrumbList', $breadcrumbJsonLd['@type'] ?? '');
        $this->assertSame(
            $seo['breadcrumbs'][1]['url'] ?? '',
            $breadcrumbJsonLd['itemListElement'][1]['item'] ?? ''
        );
    }

    public function testFrontControllerModeAffectsBreadcrumbUrls(): void
    {
        $oldEnv = $_ENV['APP_USE_FRONT_CONTROLLER_URLS'] ?? null;
        $oldScript = $_SERVER['SCRIPT_NAME'] ?? null;
        $_ENV['APP_USE_FRONT_CONTROLLER_URLS'] = '1';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        try {
            $seo = $this->service->buildDetailSeo([
                'id' => 10,
                'title' => '2-комнатная квартира',
                'action_name' => 'Продажа',
                'object_name' => 'Квартира',
                'city_name' => 'Саратов',
                'street' => 'Ленина 1',
                'cost' => 5400000,
                'descr_post' => 'Отличная квартира',
                'user_id' => 1,
            ], []);
        } finally {
            if ($oldEnv === null) {
                unset($_ENV['APP_USE_FRONT_CONTROLLER_URLS']);
            } else {
                $_ENV['APP_USE_FRONT_CONTROLLER_URLS'] = $oldEnv;
            }
            if ($oldScript === null) {
                unset($_SERVER['SCRIPT_NAME']);
            } else {
                $_SERVER['SCRIPT_NAME'] = $oldScript;
            }
        }

        $this->assertSame('https://example.test/index.php/detail/10', $seo['canonical'] ?? '');
        $this->assertSame('https://example.test/index.php', $seo['breadcrumbs'][0]['url'] ?? '');
    }
}
