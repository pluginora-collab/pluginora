# Pluginora Production Readiness Checklist

This checklist separates what is ready today from what still needs to happen before Pluginora should be treated as fully production-ready for a real WooCommerce store.

## Production Readiness Snapshot

Current recommendation:

- Pluginora is ready for source control, packaging, CI, and structured staging QA.
- Pluginora is ready for controlled production use after validation against the target store's theme, tax rules, shipping rules, payment setup, and WooCommerce extensions.
- Pluginora should not yet be treated as universally production-certified for arbitrary WooCommerce stores without that staging pass.

## What Is Ready Today

- [x] Plugin bootstrap and activation flow are implemented.
- [x] WooCommerce dependency guard is implemented.
- [x] HPOS compatibility declaration is implemented.
- [x] Dynamic pricing MVP features are implemented.
- [x] Coupon engine MVP features are implemented.
- [x] Rule CRUD and admin builder APIs are implemented.
- [x] Conflict resolution modes are implemented.
- [x] Database schema install and upgrade handling are implemented.
- [x] Uninstall cleanup and database write-failure logging are implemented.
- [x] Unit tests are passing locally.
- [x] WooCommerce-backed integration tests are passing locally.
- [x] PHPCS is passing locally.
- [x] PHPStan static analysis is passing locally.
- [x] CI and release workflows are configured.
- [x] Playwright E2E smoke coverage exists for the admin workspace and storefront pricing flow.
- [x] Release `v1.0.6` is prepared to package and publish with zip and checksum artifacts.

## What Is Still Left

These items are the remaining work before making a stronger production claim for a live store.

- [ ] Run a full staging pass on the exact WordPress, WooCommerce, theme, and extension stack that will be used in production.
- [ ] Verify storefront behavior on product, cart, and checkout pages with the target theme.
- [ ] Validate compatibility with the WooCommerce extensions used on the target store.
- [ ] Validate tax, shipping, payment, and coupon edge cases on staging.
- [ ] Verify that enabling multiple active rules produces the intended conflict behavior.
- [ ] Confirm uninstall and data-retention behavior match your deployment policy.

## Strongly Recommended Hardening

These items are not strictly blockers for a controlled release, but they materially improve production confidence.

- [ ] Execute the Playwright E2E suite against the target staging environment.
- [ ] Capture and review performance timings against larger catalogs and mixed carts.
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
5. Run `composer run lint:phpstan`.
6. Run `composer verify:release`.
7. Run the Playwright E2E suite against staging.
8. Test the plugin manually on staging.
9. Push the final code to `main`.
10. Create and push the next release tag.
11. Verify the GitHub Actions release workflow artifacts.

## Recommended Current Interpretation

As of this checklist, Pluginora should be treated as:

- functional for MVP scope
- suitable for staging and structured QA
- suitable for controlled rollout after store-specific staging validation
- not yet universally production-certified for arbitrary WooCommerce stores without staging validation