# Reclaim Details

[![License AGPLv3](https://img.shields.io/badge/License-AGPLv3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![PHP 7.4+](https://img.shields.io/badge/PHP-%3E%3D7.4-8892BF.svg)](https://php.net/)
[![PSR-4](https://img.shields.io/badge/PSR--4-compliant-brightgreen.svg)](https://www.php-fig.org/psr/psr-4/)

> **Take back control of your WordPress plugin's details display**

Reclaim Details is a zero-configuration library that enables WordPress plugins to display rich "View details" information using local `readme.txt` files and assets, independent of the WordPress.org repository.

## Why Reclaim Details?

- **Independence**: Your plugin details aren't tied to WordPress.org
- **Control**: Use your own `readme.txt` content and assets
- **WordPress-native**: Displays using authentic WordPress.org-style popups
- **Zero config**: Auto-detects your plugin, no parameters needed
- **Reusable**: Works with any WordPress plugin

## Quick Start

### Installation

**Option 1: Direct Include (Recommended)**
```php
// In your main plugin file or init hook
require_once 'path/to/reclaim-details/init.php';
```

**Option 2: Composer**
```bash
composer require magicoli/reclaim-details
```

**Option 3: Manual with Autoloader**
```php
require_once 'path/to/reclaim-details/autoload.php';
new \Reclaim\Details\ReclaimDetails();
```

### That's It!

No configuration needed. The library will:
- Auto-detect your plugin
- Parse your `readme.txt` file  
- Add "View details" link to your plugin row
- Display WordPress.org-style popup with your content

## How It Works

### Before: Generic Plugin Row
```
My Awesome Plugin
A simple description from plugin headers.
Version 1.0.0 | By Author Name | Visit plugin site
```

### After: Rich Plugin Details
```
My Awesome Plugin  
A comprehensive description with features and details.
Version 1.0.0 | By Author Name | Visit plugin site | View details
```

Clicking **"View details"** opens a beautiful WordPress.org-style popup containing all your `readme.txt` content.

## Required Files

The library reads from standard WordPress plugin structure:

```
your-plugin/
├── your-plugin.php        # Main plugin file (auto-detected)
├── readme.txt             # WordPress readme format
└── assets/                # Optional screenshots
    ├── screenshot-1.png
    ├── screenshot-2.jpg
    └── ...
```

### Example `readme.txt`
```
=== My Awesome Plugin ===
Contributors: yourname
Tags: awesome, plugin
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later

Short description of your plugin.

== Description ==

Detailed description with **formatting**.

= Features =
* Feature one
* Feature two

== Installation ==

1. Upload the plugin
2. Activate it
3. Configure settings

== Changelog ==

= 1.0.0 =
* Initial release
```

## **Advanced Usage**

### Manual Plugin Specification
```php
// If auto-detection doesn't work for your setup
new \Reclaim\Details\ReclaimDetails('/path/to/your/plugin.php');
```

### Integration Examples

**In Plugin Constructor:**
```php
class MyPlugin {
    public function __construct() {
        add_action('init', [$this, 'init']);
    }
    
    public function init() {
        require_once plugin_dir_path(__FILE__) . 'lib/reclaim-details/init.php';
        // ... rest of your init code
    }
}
```

**Direct in Main Plugin File:**
```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 */

// Load Reclaim Details
require_once __DIR__ . '/vendor/magicoli/reclaim-details/init.php';

// Your plugin code...
```

## Supported Content

### Readme Sections
- **Description** - Rich formatted content  
- **Installation** - Step-by-step instructions
- **Changelog** - Version history
- **FAQ** - Frequently asked questions
- **Screenshots** - Automatic asset detection

### Metadata
- Plugin name, version, author
- WordPress compatibility (`Requires at least`, `Tested up to`)
- PHP version requirements
- License information

### Assets
- Screenshots (`assets/screenshot-1.png`, etc.)
- Banners (planned)
- Icons (planned)

## Technical Details

### Requirements
- **PHP**: 7.4 or higher
- **WordPress**: 5.0 or higher
- **File**: `readme.txt` in plugin root

### Features
- **PSR-4 Compliant**: Proper autoloading
- **Zero Dependencies**: Uses only WordPress core functions
- **Smart Detection**: Finds plugin files automatically
- **WordPress Native**: Uses official `plugins_api` filter
- **Memory Efficient**: Loads only when needed

## Architecture

```
lib/reclaim-details/
├── composer.json              # Composer package
├── init.php                  # One-line integration
├── autoload.php             # PSR-4 autoloader
└── src/Reclaim/Details/
    └── ReclaimDetails.php   # Main class
```

## Development

### Testing the Library
```bash
# Check syntax
php -l src/Reclaim/Details/ReclaimDetails.php

# Test autoloader
php -r "require 'autoload.php'; var_dump(class_exists('Reclaim\\Details\\ReclaimDetails'));"
```

### Integration Testing
1. Add to your test plugin
2. Check WordPress admin → Plugins
3. Look for "View details" in plugin description
4. Click to verify popup content

## Examples in the Wild

- **OSProjects Plugin** - The original implementation
- *Your plugin here!* - Submit a PR to add your plugin

## 🤝 **Contributing**

1. Fork the repository
2. Create your feature branch
3. Test with multiple plugin setups
4. Submit a pull request

## 📄 **License**

This project is licensed under the AGPL-3.0-or-later License - see the [LICENSE](LICENSE) file for details.

## 🙏 **Acknowledgments**


- **capevace** for their [wp-package-parser](https://github.com/capevace/wp-package-parser) which saved me a lot of time... once I found it
- WordPress core team for the `plugins_api` system
- The open source community for inspiration
- Plugin developers who deserve control over their plugin presentation

**Part of the Reclaim Suite**: Taking back control of WordPress plugin independence
- [x] **Reclaim Details** - Plugin information display
- [ ] **Reclaim Updates** - Plugin update system (coming soon)

---
**Made with ❤️ by [Magìiíc](https://magiiic.com)**
