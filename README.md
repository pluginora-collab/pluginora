# Pluginora

[![CI](https://github.com/pluginora-collab/pluginora/actions/workflows/ci.yml/badge.svg)](https://github.com/pluginora-collab/pluginora/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/pluginora-collab/pluginora)](https://github.com/pluginora-collab/pluginora/releases)

Pluginora is a modular WooCommerce extension for dynamic pricing and coupon orchestration with a guided admin workflow.

## Highlights

- Guided rule builder for Dynamic Pricing and Coupon Engine rules.
- Product, tiered, and cart-level discount execution.
- Native coupon sync, auto-apply logic, BOGO rewards, and available-coupon rendering.
- Conflict resolution modes for stacking and priority handling.
- PHPUnit, PHPCS, CI, and release automation for production delivery.

## Development

```bash
composer install
composer test:unit
composer test:integration
composer run lint:phpcs
```

## Releases

Current verified release: [v1.0.3](https://github.com/pluginora-collab/pluginora/releases/tag/v1.0.3)

WordPress plugin metadata remains in [readme.txt](readme.txt).