# Pluginora Production Readiness Checklist

This checklist separates what is already in place from what should still be verified before Pluginora is treated as production-ready for a real WooCommerce store.

## Current Code Status

- [x] Plugin bootstrap and activation flow are implemented.
- [x] WooCommerce dependency guard is implemented.
- [x] HPOS compatibility declaration is implemented.
- [x] Dynamic pricing MVP features are implemented.
- [x] Coupon engine MVP features are implemented.
- [x] Rule CRUD and admin builder APIs are implemented.
- [x] Unit tests are passing locally.
- [x] WooCommerce-backed integration tests are passing locally.
- [x] PHPCS is passing locally.
- [x] CI and release workflows are configured.

## Release Blocking Checks

These items should be treated as blockers before calling a release production-ready for a live store.

- [ ] Run a full staging pass on a real WordPress + WooCommerce site.
- [ ] Validate product page, cart, and checkout behavior with your active theme.
- [ ] Validate compatibility with the WooCommerce extensions used on the target store.
- [ ] Validate taxes, coupons, shipping, and payment edge cases on staging.
- [ ] Verify that enabling multiple active rules produces the intended conflict behavior.
- [ ] Confirm uninstall behavior is acceptable for your deployment policy.
- [ ] Tag and publish a fresh release after all local hardening changes are pushed.

## Strongly Recommended Hardening

These items are not strictly blockers for a controlled release, but they materially improve production confidence.

- [ ] Add real browser E2E coverage for admin builder and storefront flows.
- [ ] Add a static analysis gate such as PHPStan to CI.
- [ ] Add performance profiling against larger catalogs and mixed carts.
- [ ] Add structured operational logging beyond database write-failure logging.
- [ ] Add regression tests for broader rule interaction and mixed-promotion scenarios.

## Nice-To-Have Follow-Up Work

These are valuable, but they are not required for the current MVP to function.

- [ ] Add import and export tooling.
- [ ] Add rule simulation or preview mode.
- [ ] Add analytics and reporting.
- [ ] Add customer-segmentation conditions.
- [ ] Add nested AND/OR rule groups.
- [ ] Add multi-currency policy support.

## Suggested Release Workflow

Use this order for a disciplined release:

1. Run `composer install`.
2. Run `composer run lint:phpcs`.
3. Run `composer test:unit`.
4. Run `composer test:integration`.
5. Run `composer verify:release`.
6. Test the plugin manually on staging.
7. Push the final code to `main`.
8. Create and push the next release tag.
9. Verify the GitHub Actions release workflow artifacts.

## Recommended Current Interpretation

As of this checklist, Pluginora should be treated as:

- functional for MVP scope
- suitable for staging and structured QA
- not yet universally production-certified for arbitrary WooCommerce stores without staging validation