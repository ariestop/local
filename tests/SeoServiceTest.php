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
    }
}
