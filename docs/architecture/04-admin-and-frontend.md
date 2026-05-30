# Pluginora Admin and Frontend Experience

## 9. Admin UI Architecture

The admin should be a guided rule builder, not a single overloaded form.

### Recommended UX Pattern

Use a three-step builder on a dedicated WooCommerce submenu page.

1. Choose rule family
2. Configure conditions and actions
3. Review, priority, schedule, and save

### Why This Pattern

- It matches the requirement to start with either dynamic pricing or coupon rules.
- It hides irrelevant fields until the rule type is selected.
- It scales better when more rule types are added.
- It allows validation per step instead of one large failing form.

### UI Composition

- `RuleListPage`: searchable rule table with status filters and row actions.
- `RuleBuilderPage`: create and edit experience.
- `SettingsPage`: global conflict mode, sale badge defaults, coupon display locations.

### Rule Builder Components

- `RuleTypeSelector`
- `ScopeSelector`
- `ProductSearchMultiSelect`
- `CategorySearchMultiSelect`
- `DateRangeField`
- `TierTableEditor`
- `CartThresholdEditor`
- `BogoEditor`
- `CouponDisplayLocations`
- `PriorityAndStatusPanel`
- `ReviewSummaryPanel`

### Data Loading

- Use REST for rule CRUD and schema bootstrap.
- Use AJAX or REST product search with debounced lookup.
- Preload minimal config and localization strings via `wp_add_inline_script` only for structured data bootstrapping, not styles.

### WordPress Settings API Usage

Use Settings API for global settings only:

- conflict resolution mode
- default sale badge behavior
- available coupon locations
- debug mode

Do not use Settings API for rule CRUD. Rules are business records, not global settings.

## 10. Frontend Rendering Strategy

Frontend rendering should be thin and data-driven.

### Rendering Areas

- shop loop pricing
- single product pricing
- cart item pricing
- cart notices
- checkout notices
- coupon availability blocks
- pricing tables below add-to-cart

### Presentation Services

- `PriceHtmlRenderer`
- `SavingsMessageRenderer`
- `SaleBadgeRenderer`
- `TierPricingTableRenderer`
- `CouponListRenderer`
- `CartNoticeRenderer`

### Template Strategy

- Keep HTML in PHP templates under `templates/frontend`.
- Pass fully prepared view models from renderers.
- Escape all output in templates.
- Allow template overrides only if needed later; not required for MVP.

### Notice Strategy

Examples:

- progress-to-discount notice for cart subtotal rules
- unlocked-discount notice once threshold is met
- auto-applied coupon success notice
- available coupon discovery blocks on cart, checkout, and my account

### Styling Strategy

- Separate admin and frontend stylesheets.
- No inline styles.
- Minimal WooCommerce-consistent CSS classes.
- Add opt-in classes for themes to target if needed.

## 12. Security Considerations

- Verify nonces on all admin form submits, AJAX actions, and destructive operations.
- Check capabilities on every admin endpoint. Recommended capability baseline: `manage_woocommerce`.
- Sanitize all inbound REST and AJAX payloads with schema-aware validators.
- Escape all frontend and admin output.
- Use prepared SQL through `$wpdb->prepare()`.
- Whitelist sortable columns and filter values in rule lists.
- Prevent duplicate side effects in cart hooks by tagging Pluginora-applied adjustments.
- Restrict coupon auto-apply logic to trusted server-side evaluation only.
- Validate product and category ownership before saving references.
- Protect duplicate, activate, deactivate, and delete actions with per-action nonces.

## 18. Recommended Coding Standards

- WordPress Coding Standards with PHPCS.
- PSR-4 autoloading.
- Strict types in plugin classes where practical.
- One class per file.
- Constructor injection over service location where possible.
- Keep hook callbacks shallow and delegate immediately to services.
- Prefer immutable DTOs and value objects in engine code.
- Avoid anonymous functions for complex hook logic to keep testability high.

## 19. Suggested Naming Conventions

- Admin page slugs start with `pluginora-`.
- Action names start with `pluginora_`.
- Filter names start with `pluginora_`.
- REST namespace: `pluginora/v1`.
- Script handles: `pluginora-admin`, `pluginora-frontend`.
- Style handles: `pluginora-admin`, `pluginora-frontend`.
- Translation domain: `pluginora`.