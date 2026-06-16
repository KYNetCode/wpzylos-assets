<?php

declare(strict_types=1);

namespace WPZylos\Framework\Assets;

use WPZylos\Framework\Core\Contracts\ApplicationInterface;
use WPZylos\Framework\Core\Contracts\ServiceProviderInterface;

/**
 * Assets service provider.
 *
 * Registers AssetManager and ViteAssetResolver in the container.
 * Hooks into WordPress enqueue actions.
 *
 * @package WPZylos\Framework\Assets
 */
class AssetsServiceProvider implements ServiceProviderInterface
{
    /**
     * Register asset services.
     *
     * @param ApplicationInterface $app Application instance
     *
     * @return void
     */
    public function register(ApplicationInterface $app): void
    {
        $app->singleton(AssetManager::class, function () use ($app) {
            return new AssetManager($app->context());
        });

        $app->singleton(ViteAssetResolver::class, function () use ($app) {
            $context = $app->context();
            $manifestPath = $context->path('dist/.vite/manifest.json');
            $baseUrl = $context->url('dist');
            $devServerUrl = defined('WPZYLOS_VITE_DEV_SERVER')
                ? WPZYLOS_VITE_DEV_SERVER
                : 'http://localhost:5173';
            $forceDev = defined('WPZYLOS_VITE_DEV') && WPZYLOS_VITE_DEV;

            return new ViteAssetResolver($manifestPath, $baseUrl, $devServerUrl, $forceDev);
        });

        $app->singleton('assets', fn() => $app->make(AssetManager::class));
        $app->singleton('vite', fn() => $app->make(ViteAssetResolver::class));
    }

    /**
     * Boot asset services — hook into WordPress enqueue actions.
     *
     * @param ApplicationInterface $app Application instance
     *
     * @return void
     */
    public function boot(ApplicationInterface $app): void
    {
        /** @var AssetManager $assets */
        $assets = $app->make(AssetManager::class);

        // Frontend asset enqueueing
        add_action('wp_enqueue_scripts', function () use ($assets) {
            $assets->enqueueAll('front');
        });

        // Admin asset enqueueing
        add_action('admin_enqueue_scripts', function (string $hook) use ($assets) {
            $assets->enqueueAll('admin', $hook);
        });
    }
}
