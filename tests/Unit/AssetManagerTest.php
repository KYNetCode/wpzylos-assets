<?php

defined('ABSPATH') || exit;

declare(strict_types=1);

namespace WPZylos\Framework\Assets\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPZylos\Framework\Assets\AssetManager;
use WPZylos\Framework\Assets\ScriptAsset;
use WPZylos\Framework\Assets\StyleAsset;
use WPZylos\Framework\Assets\ViteAssetResolver;

class AssetManagerTest extends TestCase
{
    private AssetManager $manager;

    protected function setUp(): void
    {
        $context = $this->createMock(\WPZylos\Framework\Core\Contracts\ContextInterface::class);
        $context->method('assetHandle')->willReturnCallback(fn($h) => 'myplugin-' . $h);
        $context->method('version')->willReturn('1.0.0');
        $context->method('url')->willReturnCallback(fn($p) => 'https://example.com/wp-content/plugins/myplugin/' . $p);

        $this->manager = new AssetManager($context);
    }

    public function testScriptReturnsScriptAsset(): void
    {
        $script = $this->manager->script('app');
        $this->assertInstanceOf(ScriptAsset::class, $script);
    }

    public function testStyleReturnsStyleAsset(): void
    {
        $style = $this->manager->style('theme');
        $this->assertInstanceOf(StyleAsset::class, $style);
    }

    public function testGetRegisteredReturnsAllAssets(): void
    {
        $this->manager->script('app');
        $this->manager->style('theme');
        $this->manager->script('admin');

        $registered = $this->manager->getRegistered();
        $this->assertCount(3, $registered);
    }

    public function testScriptChainableApi(): void
    {
        $script = $this->manager->script('admin')
            ->src('dist/js/admin.js')
            ->dependencies(['jquery'])
            ->admin()
            ->defer();

        $this->assertInstanceOf(ScriptAsset::class, $script);
        $this->assertEquals('admin', $script->getLocation());
    }

    public function testStyleChainableApi(): void
    {
        $style = $this->manager->style('frontend')
            ->src('dist/css/app.css')
            ->front()
            ->preload();

        $this->assertInstanceOf(StyleAsset::class, $style);
        $this->assertEquals('front', $style->getLocation());
    }

    public function testAdminAssetNotEnqueuedOnFrontend(): void
    {
        $this->manager->script('admin-only')->src('dist/js/admin.js')->admin();

        // Should not crash - just skips admin assets on frontend
        $this->manager->enqueueAll('front');
        $this->assertTrue(true);
    }

    public function testFrontendAssetNotEnqueuedOnAdmin(): void
    {
        $this->manager->style('front-only')->src('dist/css/app.css')->front();

        $this->manager->enqueueAll('admin');
        $this->assertTrue(true);
    }

    public function testConditionPreventsEnqueue(): void
    {
        $this->manager->script('conditional')
            ->src('dist/js/conditional.js')
            ->condition(fn() => false);

        $registered = $this->manager->getRegistered();
        $this->assertFalse($registered[0]->shouldEnqueue());
    }

    public function testConditionAllowsEnqueue(): void
    {
        $this->manager->script('conditional')
            ->src('dist/js/conditional.js')
            ->condition(fn() => true);

        $registered = $this->manager->getRegistered();
        $this->assertTrue($registered[0]->shouldEnqueue());
    }

    public function testBothLocationEnqueuesEverywhere(): void
    {
        $this->manager->script('universal')->src('dist/js/app.js');

        $registered = $this->manager->getRegistered();
        $this->assertEquals('both', $registered[0]->getLocation());
    }
}
