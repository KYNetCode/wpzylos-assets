<?php

declare(strict_types=1);

namespace WPZylos\Framework\Assets;

/**
 * Vite manifest resolver — reads Vite build manifests for production assets.
 *
 * @package WPZylos\Framework\Assets
 */
class ViteAssetResolver
{
    private string $manifestPath;
    private string $baseUrl;
    private ?array $manifest = null;
    private ?string $devServerUrl = null;
    private bool $forceDev;

    /**
     * Create resolver.
     *
     * @param string      $manifestPath Absolute path to manifest.json
     * @param string      $baseUrl      Base URL for the built assets directory
     * @param string|null $devServerUrl Vite dev server URL (e.g., 'http://localhost:5173')
     * @param bool        $forceDev     Force dev mode (bypasses hot-file/connectivity checks)
     */
    public function __construct(
        string $manifestPath,
        string $baseUrl,
        ?string $devServerUrl = null,
        bool $forceDev = false
    ) {
        $this->manifestPath = $manifestPath;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->devServerUrl = $devServerUrl ? rtrim($devServerUrl, '/') : null;
        $this->forceDev = $forceDev;
    }

    /**
     * Check if Vite dev server is running.
     *
     * When $forceDev is true (e.g. WPZYLOS_VITE_DEV constant), dev mode is
     * enabled immediately without probing the dev server.
     *
     * @return bool
     */
    public function isDev(): bool
    {
        // Explicit toggle — skip network probe entirely
        if ($this->forceDev && $this->devServerUrl) {
            return true;
        }

        if (!$this->devServerUrl) {
            return false;
        }

        // Check for hot file (created by Vite)
        $hotFile = dirname($this->manifestPath) . '/hot';
        if (file_exists($hotFile)) {
            return true;
        }

        // Check dev server connectivity with short timeout
        $response = @file_get_contents(
            $this->devServerUrl . '/@vite/client',
            false,
            stream_context_create([
                'http' => ['timeout' => 1, 'ignore_errors' => true],
            ])
        );

        return $response !== false;
    }

    /**
     * Get the dev server URL.
     *
     * @return string
     */
    public function getDevServerUrl(): string
    {
        // Check for hot file first
        $hotFile = dirname($this->manifestPath) . '/hot';
        if (file_exists($hotFile)) {
            return rtrim(trim(file_get_contents($hotFile)), '/');
        }

        return $this->devServerUrl ?? 'http://localhost:5173';
    }

    /**
     * Resolve an entry point from the manifest.
     *
     * @param string $entry Entry point path (e.g., 'src/main.js')
     *
     * @return array{url: string, css: string[], imports: string[]}|null
     */
    public function resolve(string $entry): ?array
    {
        $manifest = $this->getManifest();

        if (!$manifest || !isset($manifest[$entry])) {
            return null;
        }

        $chunk = $manifest[$entry];

        $result = [
            'url' => $this->baseUrl . '/' . ($chunk['file'] ?? ''),
            'css' => [],
            'imports' => [],
        ];

        // Collect CSS
        if (isset($chunk['css'])) {
            foreach ($chunk['css'] as $css) {
                $result['css'][] = $this->baseUrl . '/' . $css;
            }
        }

        // Collect imports
        if (isset($chunk['imports'])) {
            foreach ($chunk['imports'] as $importKey) {
                if (isset($manifest[$importKey])) {
                    $result['imports'][] = $this->baseUrl . '/' . $manifest[$importKey]['file'];
                }
            }
        }

        return $result;
    }

    /**
     * Enqueue a Vite entry point — auto-handles JS + CSS.
     *
     * @param string $entry Entry point (e.g., 'src/main.js')
     * @param string $handle WordPress asset handle
     * @param string[] $deps Additional WP dependencies
     *
     * @return void
     */
    public function enqueueEntry(string $entry, string $handle, array $deps = []): void
    {
        if ($this->isDev()) {
            $this->enqueueDevEntry($entry, $handle, $deps);

            return;
        }

        $this->enqueueProductionEntry($entry, $handle, $deps);
    }

    /**
     * Enqueue dev server entry (HMR mode).
     *
     * @param string $entry Entry point
     * @param string $handle Asset handle
     * @param string[] $deps Dependencies
     *
     * @return void
     */
    private function enqueueDevEntry(string $entry, string $handle, array $deps): void
    {
        $devUrl = $this->getDevServerUrl();

        // Enqueue Vite client for HMR
        wp_enqueue_script(
            $handle . '-vite-client',
            $devUrl . '/@vite/client',
            [],
            null,
            false
        );

        // Add type="module" to the client script
        add_filter('script_loader_tag', function (string $tag, string $h) use ($handle) {
            if ($h === $handle . '-vite-client' || $h === $handle) {
                return str_replace(' src', ' type="module" src', $tag);
            }

            return $tag;
        }, 10, 2);

        // Enqueue the entry point
        wp_enqueue_script(
            $handle,
            $devUrl . '/' . $entry,
            array_merge([$handle . '-vite-client'], $deps),
            null,
            true
        );
    }

    /**
     * Enqueue production entry from manifest.
     *
     * @param string $entry Entry point
     * @param string $handle Asset handle
     * @param string[] $deps Dependencies
     *
     * @return void
     */
    private function enqueueProductionEntry(string $entry, string $handle, array $deps): void
    {
        $resolved = $this->resolve($entry);

        if (!$resolved) {
            return;
        }

        // Enqueue the main script
        wp_enqueue_script($handle, $resolved['url'], $deps, null, true);

        // Add type="module"
        add_filter('script_loader_tag', function (string $tag, string $h) use ($handle) {
            if ($h === $handle) {
                return str_replace(' src', ' type="module" src', $tag);
            }

            return $tag;
        }, 10, 2);

        // Enqueue CSS files
        foreach ($resolved['css'] as $i => $cssUrl) {
            wp_enqueue_style($handle . '-css-' . $i, $cssUrl, [], null);
        }

        // Preload imports
        foreach ($resolved['imports'] as $importUrl) {
            add_action('wp_head', function () use ($importUrl) {
                printf('<link rel="modulepreload" href="%s">' . "\n", esc_url($importUrl));
            });
        }
    }

    /**
     * Read and cache the Vite manifest.
     *
     * @return array|null
     */
    private function getManifest(): ?array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        if (!file_exists($this->manifestPath)) {
            return null;
        }

        $content = file_get_contents($this->manifestPath);

        if ($content === false) {
            return null;
        }

        $this->manifest = json_decode($content, true);

        return $this->manifest;
    }
}
