<?php

defined('ABSPATH') || exit;

declare(strict_types=1);

/**
 * PHPUnit bootstrap for assets package.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Mock WordPress functions used by assets
if (!function_exists('wp_register_script')) {
    function wp_register_script(string $handle, string $src, array $deps = [], $ver = false, $args = false): void {}
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], $ver = false, $args = false): void {}
}

if (!function_exists('wp_deregister_script')) {
    function wp_deregister_script(string $handle): void {}
}

if (!function_exists('wp_register_style')) {
    function wp_register_style(string $handle, string $src, array $deps = [], $ver = false, string $media = 'all'): void {}
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all'): void {}
}

if (!function_exists('wp_deregister_style')) {
    function wp_deregister_style(string $handle): void {}
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $name, array $data): bool { return true; }
}

if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script(string $handle, string $data, string $position = 'after'): bool { return true; }
}

if (!function_exists('wp_add_inline_style')) {
    function wp_add_inline_style(string $handle, string $data): bool { return true; }
}

if (!function_exists('add_action')) {
    function add_action(string $tag, callable $callback, int $priority = 10, int $args = 1): void {}
}

if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $callback, int $priority = 10, int $args = 1): void {}
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string { return $url; }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string { return 'https://example.com/wp-admin/' . $path; }
}

if (!function_exists('plugins_url')) {
    function plugins_url(string $path = '', string $plugin = ''): string { return 'https://example.com/wp-content/plugins/' . $path; }
}
