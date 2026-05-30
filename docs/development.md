# Pluginora Development Guide

## Local setup

1. Install PHP 8.1+ and Composer.
2. Run `composer install` inside the plugin directory.
3. Place the plugin in a WordPress installation with WooCommerce active.
4. Activate Pluginora from the admin.

## Common commands

```bash
composer validate
composer run lint:phpcs
composer test:unit
composer test:integration:setup
composer test:integration
composer build:release
composer verify:release
composer check
```

`composer test:integration` will auto-discover a local `wordpress-develop` checkout in common locations such as `~/wordpress-develop/tests/phpunit`.
Use `WP_TESTS_DIR` and `PLUGINORA_WC_PLUGIN_FILE` only when your setup lives somewhere non-standard.
On a fresh machine, run `composer test:integration:setup` once to clone `wordpress-develop`, create the test database, configure `wp-tests-config.php`, and install WooCommerce.

`composer build:release` creates `dist/pluginora-<version>.zip` and `dist/pluginora-<version>.zip.sha256` with a production-only vendor tree and without test, docs, CI, or local tooling files.
`composer verify:release` runs lint, unit tests, integration tests, builds the release, verifies the checksum, and checks the zip for required runtime files while rejecting common dev-only paths.

## Current quality layers

- PSR-4 autoloading via Composer
- WordPress Coding Standards via PHPCS
- PHPUnit unit test scaffold
- WordPress integration test bootstrap scaffold
- GitHub Actions CI for lint, unit tests, and WooCommerce-backed integration tests, with Composer download caching
- GitHub Actions release workflow that builds the plugin zip, emits a SHA-256 checksum, and uploads both as artifacts
- HPOS compatibility declaration during plugin bootstrap

## Recommended QA flow

1. Run `composer validate`.
2. Run `composer run lint:phpcs`.
3. Run `composer test:unit`.
4. Run `composer test:integration:setup` on new machines or whenever the local WordPress test checkout is missing.
5. Run integration tests against WordPress and WooCommerce.
6. Run `composer verify:release` before distribution.
7. Manually verify admin builder flows, pricing flows, coupon flows, and conflict modes.