# Pluginora Staging Validation Runbook

This runbook is for the exact client stack validation that still has to happen before calling Pluginora fully production-ready for a live store.

## Objective

Validate Pluginora on the real deployment stack:

- target theme
- active payment gateways
- shipping extensions and rules
- tax setup
- cache layers
- other WooCommerce extensions that affect pricing, cart, or checkout

## Preconditions

- Pluginora `v1.0.6` or later is installed on staging.
- WooCommerce is active and configured.
- The target theme is active.
- The full extension set mirrors production as closely as possible.
- Test products, coupons, and customer accounts exist.

## Required passes

### Admin

- Create a simple discount rule and verify it saves and appears in the Promotion Library.
- Create a tiered pricing rule and verify the rule reopens for editing without losing data.
- Create a basic coupon rule and verify a native WooCommerce coupon exists afterward.
- Change the conflict mode in Promotion Policy and verify the setting persists.

### Storefront

- Verify discounted pricing on shop and product pages.
- Verify sale badge and savings message output with the target theme.
- Verify cart repricing after quantity changes.
- Verify cart subtotal messaging and final totals.
- Verify checkout totals match cart totals.

### Compatibility

- Test at least one shipping method that changes available totals.
- Test at least one tax-inclusive and one tax-exclusive scenario.
- Test each enabled payment gateway through checkout up to the safe staging confirmation point.
- Test with cache enabled and after cache purge.

### Mixed promotion scenarios

- Activate two compatible rules and verify expected stacking behavior.
- Activate a dynamic pricing rule plus an auto-apply coupon and verify `best_discount_only` behavior.
- Switch to `stack_all` and verify combined behavior intentionally changes.
- Switch to `coupon_priority` and verify coupon behavior wins where expected.

## Automation support

- Static analysis: `composer run lint:phpstan`
- Browser smoke coverage: `npm run e2e`
- Manual GitHub workflow: `.github/workflows/e2e.yml`
- Performance spot checks: `bin/profile-storefront.sh`

## Sign-Off Template

Record this before launch:

| Area | Result | Notes |
| --- | --- | --- |
| Admin builder | Pass / Fail | |
| Storefront pricing | Pass / Fail | |
| Cart and checkout totals | Pass / Fail | |
| Theme compatibility | Pass / Fail | |
| Shipping compatibility | Pass / Fail | |
| Tax compatibility | Pass / Fail | |
| Payment gateway compatibility | Pass / Fail | |
| Cache compatibility | Pass / Fail | |
| Mixed-rule behavior | Pass / Fail | |
| Uninstall/data retention policy | Pass / Fail | |