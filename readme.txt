=== Pluginora ===
Contributors: pluginora
Tags: woocommerce, dynamic pricing, discounts, coupons, bogo
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.3
License: Proprietary
License URI: https://example.com

Pluginora is a modular WooCommerce extension that combines dynamic pricing rules and coupon orchestration in a guided admin experience.

== Description ==

Pluginora provides a single promotion engine for WooCommerce stores that need both price rules and coupon automation without a fragmented admin workflow.

Current capabilities in this release:

* Guided rule builder for Dynamic Pricing and Coupon Engine rules.
* Custom rule persistence with extensible repositories.
* Dynamic pricing runtime for product discounts, tiered pricing, cart subtotal discounts, strike pricing, savings messaging, and badges.
* Coupon runtime for native coupon sync, auto-apply rules, BOGO reward management, and available coupon displays.
* Conflict resolution settings for `stack_all`, `best_discount_only`, and `coupon_priority`.
* HPOS compatibility declaration and WooCommerce dependency guards.

Supported promotion families:

* Dynamic Pricing
** Percentage or fixed discounts
** Tiered or bulk pricing
** Cart subtotal discounts
* Coupon Engine
** Basic native-backed coupons
** Auto-apply coupons
** BOGO coupon flows

This version is intended for structured staging and QA with WooCommerce and includes automated test coverage, CI validation, and packaged release artifacts.

== Installation ==

1. Download the packaged Pluginora release zip.
2. In WordPress admin, go to Plugins > Add New > Upload Plugin.
3. Upload the Pluginora zip and install it.
4. Activate WooCommerce.
5. Activate Pluginora from the WordPress admin.
6. Open WooCommerce > Pluginora to create promotion rules.
7. Open WooCommerce > Pluginora Settings to choose the conflict mode.

If you are installing from a source checkout instead of a packaged release zip, run `composer install` inside the plugin directory before activation.

== Getting Started ==

After activation, Pluginora adds two WooCommerce submenu pages:

* WooCommerce > Pluginora
* WooCommerce > Pluginora Settings

Recommended first-run flow:

1. Create a few simple WooCommerce products with known prices.
2. Go to WooCommerce > Pluginora.
3. Create one rule and set it to Active.
4. Test the storefront and cart before creating additional active rules.
5. Change the conflict mode in Pluginora Settings if you want to compare stacking behavior.

Suggested first tests:

* Create a simple percentage discount for one selected product.
* Create a tiered pricing rule and test quantity changes.
* Create a cart subtotal discount with a minimum threshold.
* Create a coupon rule with a code such as SAVE10.
* Create an auto-apply coupon rule.
* Create a BOGO rule and verify reward behavior.

== Testing ==

Recommended manual QA checks:

* Product page price rendering
* Cart and checkout discounts
* Badge and savings message output
* Native coupon creation and application
* Auto-apply coupon behavior
* BOGO reward handling
* Conflict behavior when multiple rules can apply
* Compatibility with your active theme and WooCommerce extensions

For source-based development and validation:

* Run `composer test:unit`
* Run `composer test:integration`
* Run `composer verify:release`

== Frequently Asked Questions ==

= Where do I find Pluginora after activation? =

Pluginora adds WooCommerce submenu pages for the rule builder and settings panel.

= What can I build with Pluginora right now? =

You can create dynamic pricing rules, cart subtotal discounts, basic coupons, auto-apply coupons, and BOGO promotions.

= How should I test Pluginora safely? =

Use a staging WooCommerce site, activate one rule at a time, and verify storefront, cart, checkout, and coupon behavior before combining multiple active promotions.

= Does Pluginora modify WooCommerce core files? =

No. Pluginora relies on WooCommerce hooks, CRUD objects, and native coupon storage.

= Does Pluginora support HPOS? =

Yes. The plugin declares compatibility with WooCommerce custom order tables and avoids direct legacy order storage assumptions.

= Is Pluginora already production-ready for every store? =

The plugin is validated for staging and structured QA, but live-store readiness still depends on your theme, taxes, shipping configuration, and third-party WooCommerce extensions.

== Changelog ==

= 1.0.3 =

* Align plugin metadata, release packaging, and GitHub artifacts with the published version.
* Add fuller setup, usage, and testing documentation.

= 1.0.2 =

* Fix GitHub Actions integration path discovery for the WordPress test suite.
* Publish the corrected CI-verified release package.

= 1.0.1 =

* Internal release iteration superseded by 1.0.2.

= 1.0.0 =

* Initial architecture scaffold.
* Guided admin rule builder.
* Dynamic Pricing engine foundation.
* Coupon Engine foundation.
* Conflict resolution settings.