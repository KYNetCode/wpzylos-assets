<?php

declare(strict_types=1);

namespace WPZylos\Framework\Assets;

/**
 * CSS stylesheet asset wrapper.
 *
 * @package WPZylos\Framework\Assets
 */
class StyleAsset extends Asset
{
    protected string $media = 'all';
    protected bool $isPreload = false;

    /**
     * Set the media attribute.
     *
     * @param string $media Media query (e.g., 'screen', 'print', 'all')
     *
     * @return static
     */
    public function media(string $media): static
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Preload this stylesheet for performance.
     *
     * @return static
     */
    public function preload(): static
    {
        $this->isPreload = true;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function enqueue(): void
    {
        $this->register();
        wp_enqueue_style($this->handle);
        $this->enqueued = true;
    }

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        wp_register_style(
            $this->handle,
            $this->sourceUrl,
            $this->deps,
            $this->ver,
            $this->media
        );

        // Inline style
        if ($this->inlineCode) {
            wp_add_inline_style($this->handle, $this->inlineCode);
        }

        // Preload link
        if ($this->isPreload) {
            add_action('wp_head', function () {
                printf(
                    '<link rel="preload" href="%s" as="style">',
                    esc_url($this->sourceUrl)
                );
            }, 1);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deregister(): void
    {
        wp_deregister_style($this->handle);
    }
}
