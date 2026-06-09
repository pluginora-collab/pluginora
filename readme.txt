=== Pluginora ===
Contributors: pluginora
Tags: woocommerce, dynamic pricing, discounts, coupons, bogo
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.5
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

Production readiness at this point:

* Ready today
** Plugin bootstrap, dependency checks, HPOS compatibility, and admin rule management are implemented.
** Dynamic pricing, basic coupons, auto-apply coupons, and BOGO flows are implemented for the current MVP scope.
** Unit tests, WooCommerce-backed integration tests, CI, and packaged release artifacts are in place.
* Still left before a stronger live-store claim
** Run a full staging pass on the exact theme, tax, shipping, payment, and extension stack used by the target store.
** Validate mixed-rule behavior when multiple active promotions can apply together.
** Add browser E2E coverage, static analysis, and performance profiling if you want a higher operational confidence bar.

== Installation ==

1. Download the packaged Pluginora release zip.
2. In WordPress admin, go to Plugins > Add New > Upload Plugin.
3. Upload the Pluginora zip and install it.
4. Activate WooCommerce.
5. Activate Pluginora from the WordPress admin.
6. Open the top-level Pluginora menu to create promotion rules.
7. Use the embedded Promotion Policy settings card inside the Pluginora workspace to choose the conflict mode.

If you are installing from a source checkout instead of a packaged release zip, run `composer install` inside the plugin directory before activation.

== Run Pluginora Step By Step ==

Fastest path using the release zip:

1. Download the latest Pluginora zip.
2. Start your WordPress site.
3. Log in as an administrator.
4. Upload the zip from Plugins > Add New > Upload Plugin.
5. Install and activate WooCommerce.
6. Activate Pluginora.
7. Open the top-level Pluginora workspace.
8. Review the embedded Promotion Policy settings card and keep the default conflict mode for the first run.
9. Create one active rule.
10. Test the storefront, product page, cart, and checkout.

Run from source checkout:

1. Clone the Pluginora repository.
2. Run `composer install`.
3. Place the folder in `wp-content/plugins/pluginora`.
4. Start WordPress and your database.
5. Activate WooCommerce.
6. Activate Pluginora.
7. Open the Pluginora admin pages and begin testing.

Recommended source validation order:

1. Run `composer test:unit`.
2. Run `composer test:integration:setup` on a fresh machine.
3. Run `composer test:integration`.
4. Run `composer verify:release`.

== Getting Started ==

After activation, Pluginora adds a single top-level Pluginora admin workspace.

The main workspace includes:

* The guided rule builder
* The promotion library
* The embedded Promotion Policy settings card

Recommended first-run flow:

1. Create a few simple WooCommerce products with known prices.
2. Go to WooCommerce > Pluginora.
3. Create one rule and set it to Active.
4. Test the storefront and cart before creating additional active rules.
5. Change the embedded Promotion Policy conflict mode if you want to compare stacking behavior.

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
* Run `composer test:integration:setup` on a fresh machine
* Run `composer test:integration`
* Run `composer verify:release`

== Frequently Asked Questions ==

= Where do I find Pluginora after activation? =

Pluginora adds a top-level Pluginora workspace for the rule builder, rule library, and embedded promotion policy settings.

= What can I build with Pluginora right now? =

You can create dynamic pricing rules, cart subtotal discounts, basic coupons, auto-apply coupons, and BOGO promotions.

= How should I test Pluginora safely? =

Use a staging WooCommerce site, activate one rule at a time, and verify storefront, cart, checkout, and coupon behavior before combining multiple active promotions.

= Does Pluginora modify WooCommerce core files? =

No. Pluginora relies on WooCommerce hooks, CRUD objects, and native coupon storage.

= Does Pluginora support HPOS? =

Yes. The plugin declares compatibility with WooCommerce custom order tables and avoids direct legacy order storage assumptions.

= Is Pluginora already production-ready for every store? =

No. Pluginora is ready for staging and controlled rollout validation, but a real production claim still depends on passing staging tests against your exact theme, taxes, shipping configuration, payment setup, and third-party WooCommerce extensions.

== Changelog ==


= 1.0.5 =

* Introduce a single top-level Pluginora admin workspace instead of split WooCommerce submenu pages.
* Redesign the admin builder and promotion library for a more professional workflow.
* Improve admin microcopy, search, and rule-management UX.
* Default new rule priority to 1 instead of 10.
* Improve storefront sale badge visibility for Pluginora-backed discounts.

= 1.0.4 =

* Add upgrade-safe schema checks during normal plugin boot.
* Add uninstall cleanup for Pluginora tables and settings.
* Add structured database failure logging for repository writes.
* Add REST route argument validation for rules and lookups.
* Add production-readiness checklist documentation.

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