# Changelog

All notable changes to Pico CMS for Nextcloud will be documented in this file.

## [2.0.0-beta.1] - 2026-04-16

### Changed

- **Nextcloud 30+ Support**: Updated compatibility to Nextcloud 30-33
- **PHP 8.2+ Required**: Minimum PHP version is now 8.2
- **Forked Pico CMS Inline**: Pico CMS v2.1.4 is now bundled directly in `lib/Pico/` instead of as a Composer dependency, allowing for Nextcloud-specific patches and Twig 3.x compatibility
- **Twig 3.x**: Upgraded from Twig 2.x to Twig 3.8+
- **Symfony YAML 6/7**: Updated YAML parser to modern Symfony versions
- **Modern Nextcloud APIs**:
  - Replaced deprecated `\OC::$server->query()` with `\OCP\Server::get()`
  - Implemented `IBootstrap` interface (removed legacy `app.php`)
  - Updated database queries to use `executeQuery()` / `executeStatement()`

### Technical Changes

- Added `lib/Pico/Core/Pico.php` - Forked Pico engine with Twig 3.x compatibility
- Added `lib/Pico/Core/PicoTwigExtension.php` - Twig extension for Pico
- Added `lib/Pico/Plugin/AbstractPicoPlugin.php` - Plugin base class
- Added `lib/Pico/bootstrap.php` - Global class aliases for backward compatibility
- Added Factory classes for dependency injection:
  - `lib/Model/WebsiteFactory.php`
  - `lib/Model/ThemeFactory.php`
  - `lib/Model/TemplateFactory.php`
  - `lib/Model/PluginFactory.php`

### Removed

- Removed `appinfo/app.php` (replaced by IBootstrap)
- Removed external `picocms/pico` dependency
- Removed `picocms/pico-theme` dependency
- Removed `picocms/pico-deprecated` dependency

### Fixed

- PHP 8.2 compatibility issues with null handling in Pico core
- Circular dependency in WebsiteFactory/WebsitesService

## [1.x and earlier]

See the Git history for changes prior to the Nextcloud 30+ modernization.
