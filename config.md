# Pluginora Context Handoff

This file is a context handoff for another model or engineer who needs to continue work on this repository without reading the full chat history.

## Repository Identity

- Project name: Pluginora
- Repository path: `/Users/abhishektiwari/pluginora`
- GitHub repository: `pluginora-collab/pluginora`
- Current branch used in this session: `main`
- Exact current checkout can be confirmed with `git rev-parse --short HEAD`
- Current published release: `v1.0.6`
- Release URL: `https://github.com/pluginora-collab/pluginora/releases/tag/v1.0.6`
- Latest layout iteration: post-`v1.0.6` workspace simplification (compact summary bar, no guided overview)

## What Pluginora Is

Pluginora is a WooCommerce plugin for promotion management. It combines:

- Dynamic Pricing
- Coupon Engine
- BOGO promotion handling
- Conflict resolution between pricing and coupon behavior

It is implemented as a modular OOP WordPress plugin with custom Pluginora rule tables and WooCommerce-native coupon storage.

## Current Product State

As of this handoff:

- `v1.0.6` is released and published.
- The published zip now matches the redesigned admin workspace.
- The plugin is functional for MVP scope.
- Unit tests, WooCommerce-backed integration tests, PHPCS, PHPStan, CI, and release packaging are in place.
- Playwright E2E smoke coverage, staging validation documentation, and profiling helpers are now in the repository.
- The plugin should still be treated as staging-ready / controlled-rollout ready rather than universally production-certified.

## Major Functional Capabilities

### Dynamic Pricing

- `simple_discount`
- `tiered_pricing`
- `cart_subtotal_discount`
- strike pricing
- savings messaging
- sale badge rendering
- tier pricing table rendering

### Coupon Engine

- `basic_coupon`
- `auto_apply_coupon`
- `bogo_coupon`
- native WooCommerce coupon sync
- coupon availability rendering
- coupon application flow

### Conflict Modes

- `best_discount_only` (default)
- `stack_all`
- `coupon_priority`

## Major Recent Changes

These are the most important recent changes another model should know about.

### `v1.0.6` release work

- Released `v1.0.6` with the post-`v1.0.5` production hardening and preview documentation included.
- Packaged assets:
  - `dist/pluginora-1.0.6.zip`
  - `dist/pluginora-1.0.6.zip.sha256`

### Admin UX redesign

- Pluginora now uses a **single top-level Pluginora admin menu**.
- The old split WooCommerce submenu pattern was removed from the current released flow.
- Promotion settings are now embedded into the main Pluginora workspace as a `Promotion Policy` card.
- The admin workspace was redesigned with:
  - compact "Active Campaign" summary bar with horizontal stat tabs (Total Rules, Active, Inactive, Modules)
  - streamlined builder without the guided overview cards or progress steps
  - promotion library panel with search and status filtering
  - improved copy and empty states

### Admin workspace simplification (post-v1.0.6)

- Replaced the branded hero section ("Launch promotions with less guesswork") with a compact summary bar labeled "Active Campaign" showing four inline stat tabs.
- Removed the "Guided Rule Builder" header, builder overview cards (Family / Rule Type / Status / Priority summary), and the 3-step progress indicator (Choose Family → Choose Rule Type → Configure Details).
- Removed the associated CSS for `.pluginora-workspace-hero`, `.pluginora-workspace-overview`, `.pluginora-overview-stat`, `.pluginora-progress`, and `.pluginora-progress-step` variants.
- Simplified the UI preview HTML (`docs/ui-preview.html`) to match the new compact layout.
- The builder still renders Promotion Family selection, Rule Type selection, and Configuration form — only the chrome/overview wrapper was removed.

### Functional fixes from feedback

- Default rule priority changed from `10` to `1`.
- Product lookup search in the admin builder was stabilized so it does not feel like it resets while typing.
- Custom sale badges now participate in WooCommerce `is_on_sale` logic so frontend badge rendering works more reliably.

### Production hardening included in `v1.0.6`

- PHPStan static analysis was added with WordPress and WooCommerce stubs.
- CI now runs PHPStan alongside PHPCS and unit tests.
- Playwright-based E2E tooling was added for real WordPress + WooCommerce staging runs.
- A manual GitHub Actions workflow was added for browser E2E execution against staging.
- Staging validation and performance profiling runbooks were added.
- A curl-based profiling helper script was added for product, cart, and checkout timing spot checks.
- Local admin and storefront preview HTML files were committed under `docs/` for design review.

## Important Files

### Bootstrap and metadata

- `pluginora.php`
- `uninstall.php`

### Core and database

- `src/Core/Bootstrap/Plugin.php`
- `src/Core/Bootstrap/PluginFactory.php`
- `src/Core/Database/SchemaInstaller.php`
- `src/Core/Database/RuleTables.php`
- `src/Core/Settings/SettingsRepository.php`
- `src/Core/Application/ConflictResolver.php`

### Admin

- `src/Admin/Pages/RuleBuilderPage.php`
- `src/Admin/Settings/PluginSettingsPage.php`
- `src/Admin/Assets/AdminAssets.php`
- `src/Admin/Api/RulesController.php`
- `src/Admin/Api/LookupsController.php`
- `src/Admin/Forms/RuleSchemaProvider.php`
- `src/Admin/Forms/RulePayloadMapper.php`
- `assets/admin/js/admin.js`
- `assets/admin/css/admin.css`

### Frontend / presentation

- `src/Modules/DynamicPricing/Presentation/ProductPriceRenderer.php`
- `src/Modules/DynamicPricing/Presentation/CartNoticeRenderer.php`
- `src/Modules/DynamicPricing/Presentation/TierPricingTableRenderer.php`
- `src/Modules/CouponEngine/Presentation/AvailableCouponsRenderer.php`
- `assets/frontend/css/frontend.css`
- `assets/frontend/js/frontend.js`

### Repository and domain

- `src/Repository/Wpdb/WpdbRuleRepository.php`
- `src/Repository/Wpdb/WpdbRuleQueryRepository.php`
- `src/Support/Rule.php`

### Documentation

- `README.md`
- `readme.txt`
- `docs/production-readiness-checklist.md`
- `docs/staging-validation.md`
- `docs/performance-profiling.md`
- `docs/ui-preview.html`
- `docs/storefront-preview.html`

### Tooling and workflows

- `.github/workflows/ci.yml`
- `.github/workflows/e2e.yml`
- `phpstan.neon.dist`
- `phpstan-bootstrap.php`
- `package.json`
- `playwright.config.js`
- `tests/E2E/`
- `bin/profile-storefront.sh`

## Validation Commands

These are the commands used successfully during this session:

```bash
composer run lint:phpcs
composer run lint:phpstan
composer test:unit
composer test:integration
composer verify:release
```

Playwright test discovery was also validated successfully with:

```bash
npm run e2e -- --list
```

## Known Test/Validation Caveat

During the integration suite, you may see a WordPress database error line for a missing `wptests_pluginora_rules` table during a failure-path repository test. In this repo state, that output is expected for a negative test case and the suite still passes.

Do not assume that line means the whole integration suite failed. Check the final PHPUnit result.

## Production Readiness Position

Current interpretation:

- Ready for source control, CI, packaging, release workflow, and structured staging QA.
- Reasonable for controlled rollout after store-specific staging validation.
- Not yet universally production-certified for arbitrary WooCommerce stores.

Still recommended before broader production use:

- full staging pass on the exact store theme and extension stack
- tax, shipping, payment, and coupon edge-case validation
- mixed-rule behavior validation
- execution of the Playwright E2E suite against the real staging site
- performance profiling on larger catalogs and mixed carts using the new helper/runbook

## Local Preview Files Created In This Session

Two local preview files were created to visualize the UI without running a full WordPress site:

- `docs/ui-preview.html`
- `docs/storefront-preview.html`

Important:

- These are **local static preview files**.
- They are useful for design review and screenshots.
- They are now committed in the repository under `docs/`.
- They are included in the repository and were published as documentation assets alongside the `v1.0.6` source release.

If another model sees these files, it should treat them as repository documentation assets rather than release-runtime code.

## Release State At Handoff

- `main` matches the published `v1.0.6` release state at handoff time.
- GitHub release `v1.0.6` exists and is published.
- README and `readme.txt` were updated so release docs match the single top-level Pluginora workspace.
- `v1.0.4` release notes were also updated earlier to clarify the path to the newer admin UX releases.

## Recommended Starting Point For Another Model

If another model continues from here, it should do this first:

1. Read `README.md`.
2. Read this `config.md`.
3. Read `docs/staging-validation.md` and `docs/performance-profiling.md` if the goal is launch readiness.
4. If working on product behavior, run `composer test:integration` after changes.
5. If working on quality/tooling, run `composer run lint:phpstan` and inspect `.github/workflows/e2e.yml`.
6. If working on release work, run `composer verify:release` before tagging the next version.

## Good Next Tasks

Examples of sensible next work:

- run staging QA against a real WooCommerce store
- execute the Playwright E2E workflow against staging
- expand browser E2E coverage beyond the current smoke paths
- improve storefront visual styling if more customer-facing polish is desired
