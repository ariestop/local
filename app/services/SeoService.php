<?php

declare(strict_types=1);

namespace App\Services;

class SeoService
{
    private const WHITELIST_FILTERS = ['city_id', 'action_id', 'room'];
    private const NON_INDEX_FILTERS = ['price_min', 'price_max', 'sort', 'post_id'];

    public function __construct(
        private array $config
    ) {}

    public function buildIndexSeo(
        array $filters,
        string $sort,
        int $page,
        array $actions,
        array $cities,
        array $query
    ): array {
        $whitelist = $this->normalizeWhitelistFilters($filters);
        $hasNonIndexFilters = $this->hasNonIndexFilters($filters, $sort);
        $hasUnknownQuery = $this->hasUnknownQueryParams($query);
        $isFirstPage = $page <= 1;
        $isIndexable = $isFirstPage && !$hasNonIndexFilters && !$hasUnknownQuery;
        $canonical = $this->buildAbsoluteUrl('/', $whitelist);

        $cityName = '';
        foreach ($cities as $city) {
            if ((int) ($city['id'] ?? 0) === (int) ($whitelist['city_id'] ?? 0)) {
                $cityName = trim((string) ($city['name'] ?? ''));
                break;
            }
        }

        $actionName = '';
        foreach ($actions as $action) {
            if ((int) ($action['id'] ?? 0) === (int) ($whitelist['action_id'] ?? 0)) {
                $actionName = trim((string) ($action['name'] ?? ''));
                break;
            }
        }

        $titleParts = [];
        $descParts = [];
        if ($actionName !== '') {
            $titleParts[] = mb_strtolower($actionName);
            $descParts[] = $actionName;
        } else {
            $titleParts[] = 'продажа недвижимости';
            $descParts[] = 'Продажа недвижимости';
        }
        if ($cityName !== '') {
            $titleParts[] = 'в ' . $cityName;
            $descParts[] = 'в ' . $cityName;
        } else {
            $descParts[] = 'в Саратове и Энгельсе';
        }
        if (!empty($whitelist['room'])) {
            $room = (int) $whitelist['room'];
            $titleParts[] = $room . '-комнатные';
            $descParts[] = $room . '-комнатные варианты';
        }

        $title = ucfirst(trim(implode(' ', $titleParts))) . ' | Квадратный метр';
        $description = trim(implode(', ', $descParts)) . '. Актуальные объявления с фото и ценами.';
        $breadcrumbs = [
            [
                'position' => 1,
                'name' => 'Главная',
                'url' => $this->buildAbsoluteUrl('/'),
            ],
        ];

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $isIndexable ? 'index,follow' : 'noindex,follow',
            'og_type' => 'website',
            'breadcrumbs' => $breadcrumbs,
            'json_ld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => (string) ($this->config['app']['name'] ?? 'Квадратный метр'),
                    'url' => $this->buildAbsoluteUrl('/'),
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => (string) ($this->config['app']['name'] ?? 'Квадратный метр'),
                    'url' => $this->buildAbsoluteUrl('/'),
                ],
                $this->buildBreadcrumbJsonLd($breadcrumbs),
            ],
        ];
    }

    public function buildDetailSeo(array $post, array $photos): array
    {
        $postId = (int) ($post['id'] ?? 0);
        $title = trim((string) ($post['title'] ?? ''));
        if ($title === '') {
            $title = trim((string) ($post['action_name'] ?? '') . ' ' . (string) ($post['object_name'] ?? 'недвижимость'));
        }
        $city = trim((string) ($post['city_name'] ?? ''));
        $street = trim((string) ($post['street'] ?? ''));
        $cost = (int) ($post['cost'] ?? 0);
        $title = trim($title . ($city !== '' ? ', ' . $city : '') . ($street !== '' ? ', ' . $street : ''));
        $title .= $cost > 0 ? ' — ' . number_format($cost, 0, '', ' ') . ' руб.' : '';

        $description = trim((string) ($post['descr_post'] ?? ''));
        if ($description === '') {
            $description = trim((string) ($post['object_name'] ?? 'Объект') . ', ' . $city . ', ' . $street);
        }
        $description = preg_replace('/\s+/', ' ', $description) ?? '';
        $description = mb_substr($description, 0, 180);

        $canonical = $this->buildAbsoluteUrl('/detail/' . $postId);
        $detailCrumbName = trim((string) ($post['action_name'] ?? '') . ' ' . (string) ($post['object_name'] ?? ''));
        if ($detailCrumbName === '') {
            $detailCrumbName = $postId > 0 ? 'Объявление #' . $postId : 'Объявление';
        }
        $breadcrumbs = [
            [
                'position' => 1,
                'name' => 'Главная',
                'url' => $this->buildAbsoluteUrl('/'),
            ],
            [
                'position' => 2,
                'name' => $detailCrumbName,
                'url' => $canonical,
            ],
        ];
        $imageUrl = '';
        if (!empty($photos[0]['filename'])) {
            $imageUrl = $this->buildAbsoluteUrl(
                photo_large_url((int) ($post['user_id'] ?? 0), $postId, (string) $photos[0]['filename'])
            );
        }

        $jsonLd = [
            $this->buildBreadcrumbJsonLd($breadcrumbs),
            [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $title,
                'description' => $description,
                'image' => $imageUrl !== '' ? [$imageUrl] : [],
                'offers' => [
                    '@type' => 'Offer',
                    'url' => $canonical,
                    'priceCurrency' => 'RUB',
                    'price' => $cost > 0 ? (string) $cost : '0',
                    'availability' => 'https://schema.org/InStock',
                ],
            ],
        ];

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index,follow',
            'og_type' => 'product',
            'breadcrumbs' => $breadcrumbs,
            'json_ld' => $jsonLd,
        ];
    }

    private function buildBreadcrumbJsonLd(array $breadcrumbs): array
    {
        $items = [];
        foreach ($breadcrumbs as $breadcrumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => (int) ($breadcrumb['position'] ?? 0),
                'name' => (string) ($breadcrumb['name'] ?? ''),
                'item' => (string) ($breadcrumb['url'] ?? ''),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private function normalizeWhitelistFilters(array $filters): array
    {
        $result = [];
        foreach (self::WHITELIST_FILTERS as $key) {
            $raw = $filters[$key] ?? '';
            if ($raw === '' || $raw === null) {
                continue;
            }
            $value = (int) $raw;
            if ($value > 0) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function hasNonIndexFilters(array $filters, string $sort): bool
    {
        foreach (self::NON_INDEX_FILTERS as $key) {
            $value = $filters[$key] ?? '';
            if ($key === 'sort') {
                if ($sort !== 'date_desc') {
                    return true;
                }
                continue;
            }
            if (trim((string) $value) !== '') {
                return true;
            }
        }
        return false;
    }

    private function hasUnknownQueryParams(array $query): bool
    {
        $allowed = array_merge(self::WHITELIST_FILTERS, self::NON_INDEX_FILTERS, ['page']);
        foreach (array_keys($query) as $queryKey) {
            if (!in_array((string) $queryKey, $allowed, true)) {
                return true;
            }
        }
        return false;
    }

    private function buildAbsoluteUrl(string $path, array $query = []): string
    {
        $base = rtrim((string) ($this->config['app']['url'] ?? ''), '/');
        if ($base === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $base = $scheme . '://' . $host;
        }

        $normalizedPath = '/' . ltrim($path, '/');
        if (function_exists('route_url')) {
            $normalizedPath = route_url($normalizedPath);
        }
        $url = $base . $normalizedPath;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }
}
