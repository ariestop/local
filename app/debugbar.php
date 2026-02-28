<?php

declare(strict_types=1);

/**
 * Инициализация PHP Debug Bar только для dev окружения.
 * Возвращает JavascriptRenderer или null.
 *
 * @param string $basePath путь приложения (например /test/public_html)
 * @param string|null $envAppEnv APP_ENV из config (если есть)
 * @return \DebugBar\JavascriptRenderer|null
 */
function init_debugbar(string $basePath = '', ?string $envAppEnv = null): ?object
{
    $env = $envAppEnv ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
    if ($env !== 'dev' && $env !== 'local' && $env !== 'development') {
        return null;
    }
    if (!class_exists(\DebugBar\StandardDebugBar::class)) {
        return null;
    }
    $debugbar = new \DebugBar\StandardDebugBar();
    $root = dirname(__DIR__);
    $resourcesPath = $root . '/vendor/php-debugbar/php-debugbar/resources';
    $baseUrl = (rtrim($basePath, '/') ?: '') . '/debugbar';
    if (is_dir($resourcesPath)) {
        $renderer = $debugbar->getJavascriptRenderer($baseUrl, $resourcesPath);
    } else {
        $renderer = $debugbar->getJavascriptRenderer($baseUrl);
    }
    $GLOBALS['_debugbar'] = $debugbar;
    return $renderer;
}
