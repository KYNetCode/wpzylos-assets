<?php

declare(strict_types=1);

namespace WPZylos\Framework\Assets;

use WPZylos\Framework\Core\Contracts\ContextInterface;

/**
 * JavaScript asset wrapper.
 *
 * @package WPZylos\Framework\Assets
 */
class ScriptAsset extends Asset
{
    protected bool $inFooter = false;
    protected bool $isAsync = false;
    protected bool $isDefer = false;
    protected bool $isModule = false;
    protected bool $isNoModule = false;

    /** @var array{name: string, data: array}|null */
    protected ?array $localizeData = null;

    /**
     * Load script in footer.
     *
     * @return static
     */
    public function inFooter(): static
    {
        $this->inFooter = true;

        return $this;
    }

    /**
     * Add async attribute.
     *
     * @return static
     */
    public function async(): static
    {
        $this->isAsync = true;

        return $this;
    }

    /**
     * Add defer attribute.
     *
     * @return static
     */
    public function defer(): static
    {
        $this->isDefer = true;

        return $this;
    }

    /**
     * Set script as ES module.
     *
     * @return static
     */
    public function module(): static
    {
        $this->isModule = true;

        return $this;
    }

    /**
     * Set nomodule attribute (fallback for non-module browsers).
     *
     * @return static
     */
    public function noModule(): static
    {
        $this->isNoModule = true;

        return $this;
    }

    /**
     * Localize script data (wp_localize_script).
     *
     * @param string $objectName JS object name
     * @param array<string, mixed> $data Data to pass
     *
     * @return static
     */
    public function localize(string $objectName, array $data): static
    {
        $this->localizeData = ['name' => $objectName, 'data' => $data];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function enqueue(): void
    {
        $this->register();
        wp_enqueue_script($this->handle);
        $this->enqueued = true;
    }

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        wp_register_script(
            $this->handle,
            $this->sourceUrl,
            $this->deps,
            $this->ver,
            $this->inFooter
        );

        // Localize data
        if ($this->localizeData) {
            wp_localize_script(
                $this->handle,
                $this->localizeData['name'],
                $this->localizeData['data']
            );
        }

        // Inline script
        if ($this->inlineCode) {
            wp_add_inline_script($this->handle, $this->inlineCode, 'after');
        }

        // Script attributes (async, defer, module, nomodule)
        if ($this->isAsync || $this->isDefer || $this->isModule || $this->isNoModule) {
            add_filter('script_loader_tag', function (string $tag, string $handle) {
                if ($handle !== $this->handle) {
                    return $tag;
                }

                if ($this->isAsync) {
                    $tag = str_replace(' src', ' async src', $tag);
                }

                if ($this->isDefer) {
                    $tag = str_replace(' src', ' defer src', $tag);
                }

                if ($this->isModule) {
                    $tag = str_replace(' src', ' type="module" src', $tag);
                }

                if ($this->isNoModule) {
                    $tag = str_replace(' src', ' nomodule src', $tag);
                }

                return $tag;
            }, 10, 2);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deregister(): void
    {
        wp_deregister_script($this->handle);
    }
}
