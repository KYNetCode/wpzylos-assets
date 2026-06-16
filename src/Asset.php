<?php

declare(strict_types=1);

namespace WPZylos\Framework\Assets;

use WPZylos\Framework\Core\Contracts\ContextInterface;

/**
 * Abstract base class for script and style assets.
 *
 * @package WPZylos\Framework\Assets
 */
abstract class Asset
{
    protected ContextInterface $context;
    protected string $handle;
    protected string $sourcePath = '';
    protected string $sourceUrl = '';

    /** @var string[] */
    protected array $deps = [];

    protected ?string $ver = null;
    protected bool $enqueued = false;

    /** @var callable|null */
    protected $conditionCallback = null;

    /**
     * Location: 'front', 'admin', or 'both'.
     */
    protected string $location = 'both';

    /**
     * Specific admin page hook.
     */
    protected ?string $pageHook = null;

    /**
     * Inline code to add after the asset.
     */
    protected ?string $inlineCode = null;

    /**
     * Create an asset instance.
     *
     * @param string $handle Asset handle (without prefix)
     * @param ContextInterface $context Plugin context
     */
    public function __construct(string $handle, ContextInterface $context)
    {
        $this->handle = $context->assetHandle($handle);
        $this->context = $context;
        $this->ver = $context->version();
    }

    /**
     * Set the source path relative to the plugin directory.
     *
     * @param string $path Relative path
     *
     * @return static
     */
    public function src(string $path): static
    {
        $this->sourcePath = $path;
        $this->sourceUrl = $this->context->url($path);

        return $this;
    }

    /**
     * Set an absolute URL for the asset.
     *
     * @param string $url Absolute URL
     *
     * @return static
     */
    public function url(string $url): static
    {
        $this->sourceUrl = $url;

        return $this;
    }

    /**
     * Set asset dependencies.
     *
     * @param string[] $deps WordPress dependency handles
     *
     * @return static
     */
    public function dependencies(array $deps): static
    {
        $this->deps = $deps;

        return $this;
    }

    /**
     * Set the asset version.
     *
     * @param string $version Version string
     *
     * @return static
     */
    public function version(string $version): static
    {
        $this->ver = $version;

        return $this;
    }

    /**
     * Only enqueue when the callback returns true.
     *
     * @param callable $callback Condition callback
     *
     * @return static
     */
    public function condition(callable $callback): static
    {
        $this->conditionCallback = $callback;

        return $this;
    }

    /**
     * Enqueue on admin pages only.
     *
     * @return static
     */
    public function admin(): static
    {
        $this->location = 'admin';

        return $this;
    }

    /**
     * Enqueue on frontend only.
     *
     * @return static
     */
    public function front(): static
    {
        $this->location = 'front';

        return $this;
    }

    /**
     * Enqueue on a specific admin page.
     *
     * @param string $hook Admin page hook suffix
     *
     * @return static
     */
    public function onPage(string $hook): static
    {
        $this->location = 'admin';
        $this->pageHook = $hook;

        return $this;
    }

    /**
     * Add inline code after the asset.
     *
     * @param string $code Inline JS or CSS
     *
     * @return static
     */
    public function inline(string $code): static
    {
        $this->inlineCode = $code;

        return $this;
    }

    /**
     * Check if this asset should be enqueued for the current context.
     *
     * @param string $currentHook Current admin page hook (empty for frontend)
     *
     * @return bool
     */
    public function shouldEnqueue(string $currentHook = ''): bool
    {
        // Check condition callback
        if ($this->conditionCallback && !($this->conditionCallback)()) {
            return false;
        }

        // Check page-specific constraint
        if ($this->pageHook && $currentHook !== $this->pageHook) {
            return false;
        }

        return true;
    }

    /**
     * Get the asset handle.
     *
     * @return string
     */
    public function getHandle(): string
    {
        return $this->handle;
    }

    /**
     * Get the asset location.
     *
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Enqueue this asset with WordPress.
     *
     * @return void
     */
    abstract public function enqueue(): void;

    /**
     * Register this asset without enqueueing.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Deregister this asset.
     *
     * @return void
     */
    abstract public function deregister(): void;
}
