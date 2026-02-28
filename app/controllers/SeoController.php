<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Services\PostService;

class SeoController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->postService = $container->get(PostService::class);
    }

    private PostService $postService;

    public function sitemap(): void
    {
        $base = rtrim((string) ($this->config['app']['url'] ?? ''), '/');
        if ($base === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $base = $scheme . '://' . $host;
        }

        $posts = $this->postService->getActiveForSitemap(50000);
        $filters = $this->postService->getActiveSitemapFilterValues(200);

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $xml->startElement('url');
        $xml->writeElement('loc', $base . '/');
        $xml->writeElement('changefreq', 'hourly');
        $xml->writeElement('priority', '1.0');
        $xml->endElement();

        foreach ($this->buildFilterUrls($base, $filters) as $filterUrl) {
            $xml->startElement('url');
            $xml->writeElement('loc', $filterUrl);
            $xml->writeElement('changefreq', 'daily');
            $xml->writeElement('priority', '0.7');
            $xml->endElement();
        }

        foreach ($posts as $post) {
            $id = (int) ($post['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $xml->startElement('url');
            $xml->writeElement('loc', $base . '/detail/' . $id);
            $lastmod = (string) ($post['created_at'] ?? '');
            if ($lastmod !== '') {
                $xml->writeElement('lastmod', date('c', strtotime($lastmod)));
            }
            $xml->writeElement('changefreq', 'daily');
            $xml->writeElement('priority', '0.8');
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        header('Content-Type: application/xml; charset=utf-8');
        echo $xml->outputMemory();
    }

    private function buildFilterUrls(string $base, array $filters): array
    {
        $urls = [];
        $append = static function (array &$collector, string $url): void {
            $collector[$url] = true;
        };

        foreach (($filters['city_ids'] ?? []) as $cityId) {
            $cityId = (int) $cityId;
            if ($cityId > 0) {
                $append($urls, $base . '/?' . http_build_query(['city_id' => $cityId]));
            }
        }

        foreach (($filters['action_ids'] ?? []) as $actionId) {
            $actionId = (int) $actionId;
            if ($actionId > 0) {
                $append($urls, $base . '/?' . http_build_query(['action_id' => $actionId]));
            }
        }

        foreach (($filters['rooms'] ?? []) as $room) {
            $room = (int) $room;
            if ($room > 0) {
                $append($urls, $base . '/?' . http_build_query(['room' => $room]));
            }
        }

        foreach (($filters['combos'] ?? []) as $combo) {
            $cityId = (int) ($combo['city_id'] ?? 0);
            $actionId = (int) ($combo['action_id'] ?? 0);
            $room = (int) ($combo['room'] ?? 0);
            if ($cityId <= 0 || $actionId <= 0 || $room <= 0) {
                continue;
            }
            $append($urls, $base . '/?' . http_build_query([
                'city_id' => $cityId,
                'action_id' => $actionId,
                'room' => $room,
            ]));
        }

        return array_keys($urls);
    }
}
