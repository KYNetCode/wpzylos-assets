<?php

declare(strict_types=1);

namespace WPZylos\Framework\Assets;

use WPZylos\Framework\Core\Contracts\ContextInterface;

/**
 * Central asset manager — factory for registering scripts and styles.
 *
 * @package WPZylos\Framework\Assets
 */
class AssetManager
{
    private ContextInterface $context;

    /** @var Asset[] */
    private array $registered = [];

    /**
     * Create asset manager.
     *
     * @param ContextInterface $context Plugin context
     */
    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * Register a JavaScript asset.
     *
     * @param string $handle Handle name (auto-prefixed)
     *
     * @return ScriptAsset
     */
    public function script(string $handle): ScriptAsset
    {
        $asset = new ScriptAsset($handle, $this->context);
        $this->registered[] = $asset;

        return $asset;
    }

    /**
     * Register a CSS stylesheet asset.
     *
     * @param string $handle Handle name (auto-prefixed)
     *
     * @return StyleAsset
     */
    public function style(string $handle): StyleAsset
    {
        $asset = new StyleAsset($handle, $this->context);
        $this->registered[] = $asset;

        return $asset;
    }

    /**
     * Enqueue all registered assets that match the current context.
     *
     * @param string $location 'front' or 'admin'
     * @param string $currentHook Current admin page hook (for onPage filtering)
     *
     * @return void
     */
    public function enqueueAll(string $location = 'front', string $currentHook = ''): void
    {
        foreach ($this->registered as $asset) {
            $assetLocation = $asset->getLocation();

            // Check location match
            if ($assetLocation !== 'both' && $assetLocation !== $location) {
                continue;
            }

            // Check conditions
            if (!$asset->shouldEnqueue($currentHook)) {
                continue;
            }

            $asset->enqueue();
        }
    }

    /**
     * Get all registered assets.
     *
     * @return Asset[]
     */
    public function getRegistered(): array
    {
        return $this->registered;
    }
}
