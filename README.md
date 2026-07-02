# Pluginora

[![CI](https://github.com/pluginora-collab/pluginora/actions/workflows/ci.yml/badge.svg)](https://github.com/pluginora-collab/pluginora/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/pluginora-collab/pluginora)](https://github.com/pluginora-collab/pluginora/releases)

Pluginora is a modular WooCommerce extension for dynamic pricing and coupon orchestration with a compact admin workflow.

## Current Status

Pluginora is currently packaged and verified as `v1.0.8`.

- GitHub Actions CI is passing on `main`.
- Unit tests and WooCommerce-backed integration tests are passing.
- PHPStan static analysis is passing.
- The latest packaged release artifact is `pluginora-1.0.8.zip`.
- The current codebase should be treated as production-capable for MVP scope after store-specific staging validation.

Current verified release: [v1.0.8](https://github.com/pluginora-collab/pluginora/releases/tag/v1.0.8)

## Production Readiness Snapshot

Current recommendation:

- Ready for source control, packaging, CI, and structured staging QA.
- Ready for controlled production use when validated against the target store's theme, tax rules, shipping rules, payment setup, and WooCommerce extensions.
- Not yet universally production-certified for arbitrary WooCommerce stores without that staging pass.

## What Is Ready Today

- Plugin bootstrap, activation flow, dependency checks, and HPOS compatibility declaration are implemented.
- Dynamic Pricing MVP is implemented, including `simple_discount`, `tiered_pricing`, and `cart_subtotal_discount` rules.
- Coupon Engine MVP is implemented, including `basic_coupon`, `auto_apply_coupon`, and `bogo_coupon` rules.
- Admin rule builder, settings page, lookup endpoints, and rule CRUD APIs are implemented.
- Conflict resolution modes are implemented for `stack_all`, `best_discount_only`, and `coupon_priority`.
- Database schema install and upgrade handling are implemented.
- Uninstall cleanup and database write-failure logging are implemented.
- PHPUnit unit tests, WooCommerce-backed integration tests, PHPCS, PHPStan, CI, and release packaging are in place.
- Playwright E2E coverage is scaffolded for the Pluginora admin workspace and storefront pricing flow.
- Release `v1.0.8` is packaged and published with zip and checksum artifacts.

## What Is Still Left

These items are the remaining work before making a stronger production claim for a live store:

- Run a full staging pass on the exact WordPress, WooCommerce, theme, and extension stack that will be used in production.
- Verify storefront behavior on product, cart, and checkout pages with the target theme.
- Validate tax, shipping, payment, and coupon edge cases on staging.
- Validate mixed-promotion behavior when multiple active rules are enabled at the same time.
- Confirm uninstall and data-retention behavior matches your deployment policy.
- Run the Playwright E2E suite against the target staging site.
- Run the performance profiling workflow against product, cart, and checkout URLs with realistic catalog and mixed-cart data.

## Highlights

- Compact summary bar with Active Campaign stats.
- Streamlined rule builder for Dynamic Pricing and Coupon Engine rules.
- Product, tiered, and cart-level discount execution.
- Native coupon sync, auto-apply logic, BOGO rewards, and available-coupon rendering.
- Conflict resolution modes for `stack_all`, `best_discount_only`, and `coupon_priority`.
- PHPUnit, PHPCS, CI, and release automation for production delivery.
- HPOS compatibility declaration and WooCommerce dependency guards.

## Requirements

- WordPress 6.5+
- WooCommerce active
- PHP 8.1+
- Composer 2.x for source-based installs and local development
- MySQL or MariaDB for local WordPress testing

## Quick Start

If you want the fastest way to run Pluginora, use the packaged release zip on a WordPress site that already has WooCommerce installed.

1. Download `pluginora-1.0.8.zip` from the [releases page](https://github.com/pluginora-collab/pluginora/releases).
2. Log in to WordPress admin as an administrator.
3. Go to `Plugins` -> `Add New` -> `Upload Plugin`.
4. Upload `pluginora-1.0.8.zip` and click `Install Now`.
5. Activate WooCommerce if it is not already active.
6. Activate Pluginora.
7. Open the top-level `Pluginora` workspace and create one active rule.
8. Open a product page, then the cart, then checkout to confirm the rule works end to end.

## Step-By-Step Run Guide

Choose one of these paths depending on how you want to run Pluginora.

### Before You Start

Make sure you have these basics in place first:

1. A working WordPress 6.5+ site.
2. WooCommerce installed and activated.
3. PHP 8.1+.
4. Administrator access to WordPress.
5. If you are using the source checkout instead of the release zip, Composer 2.x must be available.

If you are testing safely for the first time, use a staging or local WooCommerce site instead of a live storefront.

### Option 1: Run The Released Plugin Zip

Use this if you want the fastest way to test Pluginora in WordPress.

1. Download the latest `pluginora-1.0.8.zip` release asset.
2. Start your WordPress site and confirm you can log in to admin.
3. In WordPress admin, go to `Plugins` -> `Add New` -> `Upload Plugin`.
4. Choose `pluginora-1.0.8.zip` from your machine.
5. Click `Install Now` and wait for WordPress to unpack the plugin.
6. Click `Activate Plugin`.
7. If WooCommerce is not already active, activate WooCommerce before continuing.
8. Confirm that WordPress now shows a top-level `Pluginora` admin menu.
9. Open the `Pluginora` workspace and leave the embedded `Promotion Policy` settings at `best_discount_only` for your first test.
10. Create two or three simple WooCommerce products with known prices if your test catalog is empty.
11. Stay in the main `Pluginora` workspace.
12. Create a first rule using this safe starter flow:
	- Choose the `Dynamic Pricing` family.
	- Choose the `simple_discount` rule type.
	- Target one selected product.
	- Set a `10%` discount.
	- Enable any savings badge or message fields if you want to see frontend output.
	- Save the rule as `Active`.
13. Open the selected product on the storefront and verify the displayed price changes.
14. Add the product to cart and confirm the discount still applies.
15. Open checkout and confirm the final totals still reflect the expected promotion.
16. Return to admin and test one additional rule type such as `tiered_pricing`, `basic_coupon`, or `auto_apply_coupon`.

Expected result:

- Pluginora activates cleanly.
- The admin menus load.
- An active rule affects product, cart, or coupon behavior as configured.

### Option 2: Run Pluginora From Source In WordPress

Use this if you want to edit code locally and test changes.

1. Clone the repository to your machine.
2. Change into the project directory.
3. Install PHP dependencies with Composer.
4. Copy or symlink the repository into your WordPress plugins directory as `wp-content/plugins/pluginora`.
5. Verify the plugin folder contains the `vendor` directory after Composer finishes.
6. Start your local WordPress site and database.
7. Log in to WordPress admin.
8. Activate WooCommerce.
9. Activate Pluginora.
10. Open the top-level `Pluginora` workspace and review the embedded promotion policy settings card.
11. Create one rule in the main Pluginora workspace.
12. Test the storefront, cart, and checkout exactly as you would with the packaged zip.

Commands:

```bash
git clone https://github.com/pluginora-collab/pluginora.git
cd pluginora
composer install
```

Then place the project in WordPress as:

```text
wp-content/plugins/pluginora
```

Notes:

- If you symlink the repo into `wp-content/plugins`, WordPress will run your live checkout directly.
- If you activate Pluginora without running `composer install`, activation will be blocked because the autoloader is required.
- This path is best for local development, debugging, and repeated manual QA.

### Option 3: Run The Local Validation Suite

Use this if you want to verify that Pluginora is working as a codebase before or after changes.

1. Install PHP, Composer, MySQL or MariaDB, and the other local dependencies needed for WordPress testing.
2. Run `composer install`.
3. Run `composer test:unit` to validate the unit-level behavior.
4. Run `composer test:integration:setup` once on a fresh machine to prepare the WordPress and WooCommerce test environment.
5. Run `composer test:integration` to execute WooCommerce-backed integration coverage.
6. Run `composer run lint:phpcs` to validate coding standards.
7. Run `composer verify:release` if you want the release preflight check used before packaging.

Commands:

```bash
composer install
composer test:unit
composer test:integration:setup
composer test:integration
composer run lint:phpcs
composer run lint:phpstan
composer verify:release
```

For browser coverage against a real WordPress site:

```bash
cp tests/E2E/.env.example tests/E2E/.env.local
wp eval-file tests/E2E/bin/seed-fixtures.php >> tests/E2E/.env.local
npm install
npm run e2e:install
npm run e2e
```

Use this path when you want confidence in the codebase itself, not just a manual WordPress install.

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

## Local WordPress Setup Checklist

If you are starting from zero on a local machine, use this order:

1. Install PHP 8.1+.
2. Install Composer.
3. Install MySQL or MariaDB.
4. Create or start a local WordPress site.
5. Install and activate WooCommerce.
6. Clone Pluginora or download the release zip.
7. Run `composer install` if using the source checkout.
8. Activate Pluginora.
9. Create test products.
10. Create and verify one rule at a time.

## Detailed First Run Walkthrough

If this is your first time running Pluginora, use this exact sequence after activation:

1. In WooCommerce, create at least two simple products with clear prices such as `$100` and `$50`.
2. Open the top-level `Pluginora` workspace.
3. Review the compact summary bar showing rule counts.
4. Leave the embedded `Promotion Policy` conflict mode as `best_discount_only`.
5. Select a Promotion Family in the builder.
6. Choose the `simple_discount` rule type.
7. Target one of the products you created.
8. Set the discount to `10%`.
9. Save the rule as `Active`.
10. Open that product on the storefront and confirm the price changes.
11. Add the product to cart and confirm the discount remains applied.
12. Open checkout and confirm the total still matches the promotion.
13. Return to the builder and create a second rule such as `basic_coupon` or `tiered_pricing`.
14. Test that rule separately before enabling several rules at once.

After the first successful run, you can move on to mixed-rule testing, coupon automation, BOGO behavior, and conflict-mode checks.

## Where To Start In Admin

Pluginora now uses a single branded admin workspace:

- top-level `Pluginora`
- compact "Active Campaign" summary bar showing Total Rules, Active, Inactive, and Modules
- streamlined rule builder (family selection, rule type selection, configuration form)
- promotion library with search and status filtering
- embedded `Promotion Policy` settings card

The admin entry point is registered in [src/Admin/Pages/RuleBuilderPage.php](/Users/abhishektiwari/pluginora/src/Admin/Pages/RuleBuilderPage.php), and the settings form is defined in [src/Admin/Settings/PluginSettingsPage.php](/Users/abhishektiwari/pluginora/src/Admin/Settings/PluginSettingsPage.php).

### Rule Builder

Use `WooCommerce` -> `Pluginora` to create and manage promotion rules.

The builder is backed by REST routes under `pluginora/v1` and the rule schema in [src/Admin/Api/RulesController.php](/Users/abhishektiwari/pluginora/src/Admin/Api/RulesController.php) and [src/Admin/Forms/RuleSchemaProvider.php](/Users/abhishektiwari/pluginora/src/Admin/Forms/RuleSchemaProvider.php).

### Settings

Use the embedded `Promotion Policy` card in the main Pluginora workspace to define how Pluginora resolves conflicts between pricing rules and coupon behavior.

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

Recommended command order on a new development machine:

```bash
composer install
composer test:integration:setup
composer test:unit
composer test:integration
composer run lint:phpcs
composer verify:release
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

Pluginora is ready for staging and controlled release validation, but final production readiness still depends on your real store setup. Test on staging before using it on a live storefront.
