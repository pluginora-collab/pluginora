# E2E Test Plan

Pluginora E2E coverage should target a real WordPress + WooCommerce environment.

## Priority scenarios

1. Admin guided rule creation for dynamic pricing and coupon rules.
2. Product search and category lookup behavior in the rule builder.
3. Simple discount strike pricing on shop, product, and cart.
4. Tiered pricing display and cart recalculation when quantity changes.
5. Auto-apply coupon behavior when cart conditions become true or false.
6. BOGO reward add and remove behavior when qualifying items change.
7. Conflict mode behavior for `stack_all`, `best_discount_only`, and `coupon_priority`.

## Recommended tooling

- Playwright for browser automation
- WP-Env, Local, or Docker for the WordPress runtime
- Seed data fixtures for products, categories, and coupons