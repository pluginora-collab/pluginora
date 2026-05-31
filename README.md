# Pluginora

[![CI](https://github.com/pluginora-collab/pluginora/actions/workflows/ci.yml/badge.svg)](https://github.com/pluginora-collab/pluginora/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/pluginora-collab/pluginora)](https://github.com/pluginora-collab/pluginora/releases)

Pluginora is a modular WooCommerce extension for dynamic pricing and coupon orchestration with a guided admin workflow.

## Current Status

Pluginora is currently packaged and verified as `v1.0.3`.

- GitHub Actions CI is passing on `main`.
- Unit tests and WooCommerce-backed integration tests are passing.
- The latest packaged release artifact is `pluginora-1.0.3.zip`.

Current verified release: [v1.0.3](https://github.com/pluginora-collab/pluginora/releases/tag/v1.0.3)

## Highlights

- Guided rule builder for Dynamic Pricing and Coupon Engine rules.
- Product, tiered, and cart-level discount execution.
- Native coupon sync, auto-apply logic, BOGO rewards, and available-coupon rendering.
- Conflict resolution modes for `stack_all`, `best_discount_only`, and `coupon_priority`.
- PHPUnit, PHPCS, CI, and release automation for production delivery.
- HPOS compatibility declaration and WooCommerce dependency guards.

## Requirements

- WordPress 6.5+
- WooCommerce active
- PHP 8.1+

## Quick Start

If you want to test Pluginora in a WordPress or WooCommerce site, use the packaged release zip.

1. Download `pluginora-1.0.3.zip` from the [releases page](https://github.com/pluginora-collab/pluginora/releases).
2. In WordPress admin, go to `Plugins` -> `Add New` -> `Upload Plugin`.
3. Upload the zip and install it.
4. Activate WooCommerce if it is not already active.
5. Activate Pluginora.
6. Open `WooCommerce` -> `Pluginora` to create rules.
7. Open `WooCommerce` -> `Pluginora Settings` to configure conflict behavior.

## Install From Source

Use this path if you want to develop locally instead of installing the packaged zip.

```bash
git clone https://github.com/pluginora-collab/pluginora.git
cd pluginora
composer install
```

Then place the plugin directory in your WordPress installation as:

```text
wp-content/plugins/pluginora
```

After that:

1. Activate WooCommerce.
2. Activate Pluginora.
3. Open the WooCommerce admin menus described below.

If you activate from a source checkout without Composer dependencies, Pluginora will block activation and show an admin notice. That behavior is defined in [pluginora.php](/Users/abhishektiwari/pluginora/pluginora.php).

## Where To Start In Admin

Pluginora adds two WooCommerce submenu pages:

- `WooCommerce` -> `Pluginora`
- `WooCommerce` -> `Pluginora Settings`

These pages are registered in [src/Admin/Pages/RuleBuilderPage.php](/Users/abhishektiwari/pluginora/src/Admin/Pages/RuleBuilderPage.php) and [src/Admin/Settings/PluginSettingsPage.php](/Users/abhishektiwari/pluginora/src/Admin/Settings/PluginSettingsPage.php).

### Rule Builder

Use `WooCommerce` -> `Pluginora` to create and manage promotion rules.

The builder is backed by REST routes under `pluginora/v1` and the rule schema in [src/Admin/Api/RulesController.php](/Users/abhishektiwari/pluginora/src/Admin/Api/RulesController.php) and [src/Admin/Forms/RuleSchemaProvider.php](/Users/abhishektiwari/pluginora/src/Admin/Forms/RuleSchemaProvider.php).

### Settings

Use `WooCommerce` -> `Pluginora Settings` to define how Pluginora resolves conflicts between pricing rules and coupon behavior.

Current conflict modes:

- `best_discount_only`
- `stack_all`
- `coupon_priority`

## Supported Rule Types

Pluginora currently exposes two rule families.

### Dynamic Pricing

- `simple_discount`
	- Percentage or fixed discount
	- Target all products, selected products, or selected categories
	- Optional badge and savings message
- `tiered_pricing`
	- Quantity-based discount tiers
	- Optional frontend pricing table
- `cart_subtotal_discount`
	- Discount triggered by minimum or maximum cart amount thresholds

### Coupon Engine

- `basic_coupon`
	- Native WooCommerce-backed coupon configuration
- `auto_apply_coupon`
	- Coupon automatically applied when cart conditions match
- `bogo_coupon`
	- Buy X Get Y style promotions

## How To Use Pluginora

The simplest way to test Pluginora is to create a few sample WooCommerce products and then turn on one rule at a time.

### Suggested Sample Catalog

Create three simple products in WooCommerce:

- Product A: `$100`
- Product B: `$50`
- Product C: `$30`

### Suggested First Tests

1. Create a `Dynamic Pricing` -> `Percentage or Fixed Discount` rule.
2. Target one selected product.
3. Set a `10` percent discount.
4. Enable the badge and savings message.
5. Save the rule as `Active`.
6. Visit the product page and cart.

Expected result:

- Product pricing changes.
- Savings messaging appears.
- The discount carries through to the cart.

Then test these one by one:

#### Tiered Pricing

Create a tiered rule such as:

- Quantity `1-2`: `5%`
- Quantity `3+`: `10%`

Expected result:

- Discount level changes with quantity.
- Pricing table appears if enabled.

#### Cart Subtotal Discount

Create a cart total rule with a threshold such as `100`.

Expected result:

- No discount before threshold.
- Discount appears once cart subtotal qualifies.

#### Basic Coupon

Create a coupon rule with a code such as `SAVE10`.

Expected result:

- Coupon can be entered and applied like a native WooCommerce coupon.

#### Auto Apply Coupon

Create an auto-apply coupon rule with a cart condition.

Expected result:

- Coupon is added automatically when the cart matches the condition.

#### BOGO Coupon

Create a BOGO rule with a buy product and reward product.

Expected result:

- Reward behavior appears only when the qualifying product and quantity are present.

#### Conflict Mode

Change the mode in `WooCommerce` -> `Pluginora Settings`.

Expected result:

- Promotion behavior changes depending on whether you allow stacking, best-discount selection, or coupon priority.

## What To Check During Manual QA

- Product page price rendering
- Cart and checkout discount behavior
- Badge text and savings message display
- Coupon creation and sync behavior
- Auto-apply timing and removal behavior
- BOGO reward handling
- Interaction between multiple active rules
- Compatibility with your active theme and any WooCommerce extensions

## Development Commands

```bash
composer install
composer validate
composer run lint:phpcs
composer test:unit
composer test:integration:setup
composer test:integration
composer build:release
composer verify:release
composer check
```

## Local Integration Tests

Pluginora includes a WooCommerce-backed WordPress integration test setup.

On a fresh machine:

```bash
composer test:integration:setup
composer test:integration
```

What the setup command does:

- clones `wordpress-develop` if needed
- creates the test database
- configures `wp-tests-config.php`
- installs WooCommerce into the test WordPress checkout

`composer test:integration` auto-discovers common WordPress test locations such as `~/wordpress-develop/tests/phpunit`.

## Building A Release

To build the production zip locally:

```bash
composer build:release
```

Artifacts are created in:

```text
dist/pluginora-<version>.zip
dist/pluginora-<version>.zip.sha256
```

To run the full release preflight locally:

```bash
composer verify:release
```

## Repository Notes

- GitHub-facing project overview lives in this file.
- WordPress plugin directory metadata lives in [readme.txt](/Users/abhishektiwari/pluginora/readme.txt).
- Development notes live in [docs/development.md](/Users/abhishektiwari/pluginora/docs/development.md).

## Production Caution

Pluginora is in good shape for staging and structured QA, but production readiness for a real store still depends on your theme, taxes, shipping rules, payment setup, and third-party WooCommerce extensions. Test on staging before using it on a live storefront.