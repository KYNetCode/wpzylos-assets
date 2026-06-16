# WPZylos Assets

[![PHP Version](https://img.shields.io/badge/php-%5E8.0-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![GitHub](https://img.shields.io/badge/GitHub-KYNetCode-181717?logo=github)](https://github.com/KYNetCode/wpzylos-assets)

Asset management with Vite support for WPZylos framework.

📖 **[Full Documentation](https://wpzylos.com)** | 🐛 **[Report Issues](https://github.com/KYNetCode/wpzylos-assets/issues)**

---

## ✨ Features

- **AssetManager** — Central factory for script and style registration
- **ScriptAsset** — `async`, `defer`, `module`, `nomodule`, `localize`, inline JS
- **StyleAsset** — Media queries, preload, inline CSS
- **ViteAssetResolver** — Vite manifest.json integration with HMR dev server
- **Auto-Prefixed Handles** — Handles automatically prefixed via ContextInterface
- **Conditional Loading** — Admin-only, frontend-only, or page-specific
- **AssetsServiceProvider** — Auto-hooks into `wp_enqueue_scripts` and `admin_enqueue_scripts`

---

## 📋 Requirements

| Requirement | Version |
| ----------- | ------- |
| PHP         | ^8.0    |
| WordPress   | 6.0+    |

---

## 🚀 Installation

```bash
composer require KYNetCode/wpzylos-assets
```

---

## 📖 Quick Start

### Register Scripts & Styles

```php
use WPZylos\Framework\Assets\AssetManager;

$assets = $app->make(AssetManager::class);

// JavaScript with localize data
$assets->script('admin-app')
    ->src('dist/js/admin.js')
    ->dependencies(['jquery', 'wp-api'])
    ->admin()
    ->defer()
    ->localize('MyPluginData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_nonce'),
    ]);

// CSS Stylesheet
$assets->style('frontend')
    ->src('dist/css/app.css')
    ->front()
    ->preload();
```

### Vite Integration

```php
use WPZylos\Framework\Assets\ViteAssetResolver;

$vite = $app->make(ViteAssetResolver::class);

// Auto-detects dev vs production mode
// Dev: loads from HMR server with hot reload
// Prod: reads manifest.json, enqueues hashed assets
$vite->enqueueEntry('src/main.js', 'my-app', ['wp-api']);
```

#### HMR Dev Mode

Enable instant dev mode with the `WPZYLOS_VITE_DEV` constant — no hot file or network probe needed:

```php
// In wp-config.php or plugin bootstrap
define('WPZYLOS_VITE_DEV', true);
```

Or toggle programmatically via the `$forceDev` constructor parameter:

```php
$vite = new ViteAssetResolver(
    manifestPath: $pluginDir . '/dist/.vite/manifest.json',
    baseUrl:      plugins_url('dist/', __FILE__),
    devServerUrl: 'http://localhost:5173',
    forceDev:     true, // Skip hot-file checks, go straight to HMR
);
```

---

## 🏗️ Core Features

### Script Attributes

```php
$assets->script('modern-app')
    ->src('dist/js/app.js')
    ->module()          // type="module"
    ->defer()           // defer attribute
    ->inFooter();       // load in footer

$assets->script('legacy-fallback')
    ->src('dist/js/legacy.js')
    ->noModule();       // nomodule attribute
```

### Conditional Loading

```php
// Only on specific admin page
$assets->script('settings-page')
    ->src('dist/js/settings.js')
    ->onPage('settings_page_my-plugin');

// Only when condition is true
$assets->style('woocommerce')
    ->src('dist/css/woo.css')
    ->condition(fn() => class_exists('WooCommerce'));
```

### Inline Code

```php
$assets->script('app')
    ->src('dist/js/app.js')
    ->inline('window.CONFIG = ' . json_encode($config) . ';');

$assets->style('theme')
    ->src('dist/css/theme.css')
    ->inline(':root { --primary: ' . $color . '; }');
```

---

## 📦 Related Packages

| Package                                                                | Description            |
| ---------------------------------------------------------------------- | ---------------------- |
| [wpzylos-core](https://github.com/KYNetCode/wpzylos-core)         | Application foundation |
| [wpzylos-views](https://github.com/KYNetCode/wpzylos-views)       | View/template engine   |
| [wpzylos-hooks](https://github.com/KYNetCode/wpzylos-hooks)       | WordPress hooks        |
| [wpzylos-scaffold](https://github.com/KYNetCode/wpzylos-scaffold) | Plugin template        |

---

## 📖 Documentation

For comprehensive documentation, tutorials, and API reference, visit **[wpzylos.com](https://wpzylos.com)**.

---

## Support the Project

- [GitHub Sponsors](https://github.com/sponsors/KYNetCode)
- [PayPal Donate](https://www.paypal.com/donate/?hosted_button_id=66U4L3HG4TLCC)

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

**Made with ❤️ by [KYNetCode](https://github.com/KYNetCode)**
