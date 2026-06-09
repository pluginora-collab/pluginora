# Pluginora Context Handoff

This file is a context handoff for another model or engineer who needs to continue work on this repository without reading the full chat history.

## Repository Identity

- Project name: Pluginora
- Repository path: `/Users/abhishektiwari/pluginora`
- GitHub repository: `pluginora-collab/pluginora`
- Current branch used in this session: `main`
- Exact current checkout can be confirmed with `git rev-parse --short HEAD`
- Current published release: `v1.0.5`
- Release URL: `https://github.com/pluginora-collab/pluginora/releases/tag/v1.0.5`

## What Pluginora Is

Pluginora is a WooCommerce plugin for promotion management. It combines:

- Dynamic Pricing
- Coupon Engine
- BOGO promotion handling
- Conflict resolution between pricing and coupon behavior

It is implemented as a modular OOP WordPress plugin with custom Pluginora rule tables and WooCommerce-native coupon storage.

## Current Product State

As of this handoff:

- `v1.0.5` is released and published.
- The published zip now matches the redesigned admin workspace.
- The plugin is functional for MVP scope.
- Unit tests, WooCommerce-backed integration tests, PHPCS, CI, and release packaging are in place.
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

### `v1.0.5` release work

- Released `v1.0.5` and aligned source, docs, and release assets.
- Packaged assets:
  - `dist/pluginora-1.0.5.zip`
  - `dist/pluginora-1.0.5.zip.sha256`

### Admin UX redesign

- Pluginora now uses a **single top-level Pluginora admin menu**.
- The old split WooCommerce submenu pattern was removed from the current released flow.
- Promotion settings are now embedded into the main Pluginora workspace as a `Promotion Policy` card.
- The admin workspace was redesigned with:
  - branded hero section
  - campaign workspace summary
  - 3-step guided builder flow
  - promotion library panel with search and status filtering
  - improved copy and empty states

### Functional fixes from feedback

- Default rule priority changed from `10` to `1`.
- Product lookup search in the admin builder was stabilized so it does not feel like it resets while typing.
- Custom sale badges now participate in WooCommerce `is_on_sale` logic so frontend badge rendering works more reliably.

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

## Validation Commands

These are the commands used successfully during this session:

```bash
composer run lint:phpcs
composer test:unit
composer test:integration
composer verify:release
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
- E2E browser coverage
- static analysis such as PHPStan
- performance profiling on larger catalogs and mixed carts

## Local Preview Files Created In This Session

Two local preview files were created to visualize the UI without running a full WordPress site:

- `docs/ui-preview.html`
- `docs/storefront-preview.html`

Important:

- These are **local static preview files**.
- They are useful for design review and screenshots.
- They are currently **untracked** unless later committed.
- They are **not part of the published plugin release** unless explicitly committed and pushed.

If another model sees these files, it should ask whether to keep them, commit them under `docs/`, or remove them.

## Release State At Handoff

- Source for `v1.0.5` is on `main`.
- GitHub release `v1.0.5` exists and is published.
- README and `readme.txt` were updated so release docs match the single top-level Pluginora workspace.
- `v1.0.4` release notes were also updated earlier to clarify that later admin UX refinements had landed on `main` before `v1.0.5` was cut.

## Recommended Starting Point For Another Model

If another model continues from here, it should do this first:

1. Read `README.md`.
2. Read this `config.md`.
3. Check `git status` to see whether the two preview files are still untracked.
4. If working on product behavior, run `composer test:integration` after changes.
5. If working on release work, run `composer verify:release` before tagging.

## Good Next Tasks

Examples of sensible next work:

- commit or remove the local preview files
- run staging QA against a real WooCommerce store
- add browser E2E tests
- add PHPStan to CI
- improve storefront visual styling if more customer-facing polish is desired
