# Integration Test Notes

Pluginora integration tests are intended to run against the WordPress core test suite with WooCommerce activated.

## Current implemented suites

- `RuleRepositoryIntegrationTest`: verifies rule persistence, duplication, status updates, and filtered queries.
- `RulesControllerIntegrationTest`: verifies rule CRUD controller behavior and validation failures.
- `LookupsControllerIntegrationTest`: verifies WooCommerce-backed product and category lookup responses for the rule builder.
- `RuleBuilderPageIntegrationTest`: verifies WooCommerce submenu registration and the admin rule-builder shell output.
- `AdminAssetsIntegrationTest`: verifies Pluginora admin pages enqueue the expected CSS, JS, and inline bootstrap config.
- `FrontendAssetsIntegrationTest`: verifies storefront assets load only in supported WooCommerce contexts.
- `RuntimeHookRegistrationIntegrationTest`: verifies pricing and coupon presentation classes register their expected WooCommerce actions and filters.
- `CouponSyncIntegrationTest`: verifies native WooCommerce coupon creation, status changes, and trash behavior.
- `ConflictResolverIntegrationTest`: verifies `stack_all`, `best_discount_only`, and `coupon_priority` behavior when WooCommerce cart classes are available.
- `AutoApplyCouponsIntegrationTest`: verifies automatic coupon application, cleanup, and suppression/removal when dynamic pricing wins under conflict rules.
- `BogoCartManagerIntegrationTest`: verifies BOGO reward insertion, quantity reconciliation, removal when qualification or conflict resolution changes, reward pricing adjustments, and reward-name decoration.
- `CouponApplyHandlerIntegrationTest`: verifies nonce-checked coupon application request handling and redirect resolution.
- `AvailableCouponsRendererIntegrationTest`: verifies location-aware coupon provider filtering and rendered coupon cards.
- `CouponValidationIntegrationTest`: verifies managed coupon date-window enforcement against synced WooCommerce coupons.
- `AvailableCouponProviderEdgeCaseIntegrationTest`: verifies coupons without codes or without matching display locations stay hidden from frontend rendering.
- `CouponValidationEdgeCaseIntegrationTest`: verifies already-invalid coupons, unmanaged coupons, missing-rule lookups, and admin-context validation behavior.
- `PluginSettingsPageIntegrationTest`: verifies settings registration, conflict-mode sanitization, and authorized admin page rendering for promotion policy settings.
- `ProductPriceRendererIntegrationTest`: verifies dynamic pricing storefront price HTML and sale-badge rendering.
- `TierPricingTableRendererIntegrationTest`: verifies tier pricing tables render for matching WooCommerce products.
- `CartNoticeRendererIntegrationTest`: verifies cart subtotal promotion progress and unlock notices.
- `CartPriceAdjustmentsIntegrationTest`: verifies cart fee application plus item-price mutation, restoration, and discounted cart-line HTML rendering when conflict modes toggle dynamic pricing on or off.

## Prerequisites

- PHP 8.1+
- Composer dependencies installed
- A WordPress `wordpress-develop` checkout available locally
- WooCommerce available in that checkout or provided via `PLUGINORA_WC_PLUGIN_FILE`

## Recommended command

```bash
composer test:integration:setup
composer test:integration
```

The setup command is idempotent. It will clone `wordpress-develop` to `~/wordpress-develop` by default, create the `pluginora_test` database, update `wp-tests-config.php`, and install WooCommerce into the checkout.

## Optional overrides

- `WP_DEVELOP_DIR`: custom path for the `wordpress-develop` checkout.
- `WP_TESTS_DIR`: explicitly point to the WordPress test suite if it is not under a standard local `wordpress-develop` path.
- `PLUGINORA_WC_PLUGIN_FILE`: explicitly point to `woocommerce.php` if WooCommerce is not installed inside the detected WordPress checkout.
- `WP_TESTS_DB_NAME`, `WP_TESTS_DB_USER`, `WP_TESTS_DB_PASSWORD`, `WP_TESTS_DB_HOST`: override the MySQL test database connection used by the setup script.
- `WP_PHP_BINARY`, `MYSQL_BIN`: override the PHP and MySQL executables used by the setup script.

## Scope

Use this suite for:

- repository persistence checks
- REST controller integration
- WooCommerce cart behavior
- conflict mode integration between pricing and coupons