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

Current foundations included in this codebase:

* Guided rule builder for Dynamic Pricing and Coupon Engine rules.
* Custom rule persistence with extensible repositories.
* Dynamic pricing runtime for product discounts, tiered pricing, cart subtotal discounts, strike pricing, savings messaging, and badges.
* Coupon runtime for native coupon sync, auto-apply rules, BOGO reward management, and available coupon displays.
* Conflict resolution settings for `stack_all`, `best_discount_only`, and `coupon_priority`.
* HPOS compatibility declaration and WooCommerce dependency guards.

== Installation ==

1. Install the packaged Pluginora release into the `wp-content/plugins/pluginora` directory.
2. Activate WooCommerce.
3. Activate Pluginora from the WordPress admin.

If you are installing from a source checkout instead of a packaged release zip, run `composer install` inside the plugin directory before activation.

== Frequently Asked Questions ==

= Does Pluginora modify WooCommerce core files? =

No. Pluginora relies on WooCommerce hooks, CRUD objects, and native coupon storage.

= Does Pluginora support HPOS? =

Yes. The plugin declares compatibility with WooCommerce custom order tables and avoids direct legacy order storage assumptions.

== Changelog ==

= 1.0.3 =

* Align plugin metadata, release packaging, and GitHub artifacts with the published version.
* Add a GitHub README with CI and release badges.

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