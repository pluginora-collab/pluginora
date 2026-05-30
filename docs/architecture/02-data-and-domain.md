# Pluginora Data and Domain Design

## 3. Database Strategy

### Recommended Storage Model

Use a hybrid persistence strategy.

- Custom tables for Pluginora pricing and orchestration rules.
- Native WooCommerce coupon storage for coupons.
- WordPress options for global settings.
- Transients or object cache for short-lived computed lookup data.

### Why Custom Tables

Dynamic pricing rules need:

- structured conditions
- sortable priorities
- range queries by date
- efficient product and category matching
- duplication and status transitions
- future auditability

Storing these entirely in post meta becomes expensive and hard to maintain once rule counts grow.

### Suggested Tables

`pluginora_rules`

- `id`
- `module` (`dynamic_pricing`, `coupon_engine`)
- `rule_type` (`percentage`, `fixed`, `tiered`, `cart_total`, `bogo`, `auto_coupon`)
- `name`
- `status`
- `priority`
- `stack_mode_override` nullable
- `starts_at_gmt`
- `ends_at_gmt`
- `created_at_gmt`
- `updated_at_gmt`

`pluginora_rule_conditions`

- `id`
- `rule_id`
- `condition_type`
- `operator`
- `condition_value` JSON
- `sort_order`

`pluginora_rule_actions`

- `id`
- `rule_id`
- `action_type`
- `action_value` JSON
- `sort_order`

`pluginora_rule_items`

- `id`
- `rule_id`
- `object_type` (`product`, `category`, `excluded_product`)
- `object_id`

`pluginora_rule_tiers`

- `id`
- `rule_id`
- `min_qty`
- `max_qty`
- `discount_type`
- `discount_value`

`pluginora_rule_logs`

- `id`
- `rule_id`
- `context_type`
- `context_reference`
- `message`
- `created_at_gmt`

### Serialization Guidance

- Use JSON only for complex action payloads and condition payloads.
- Keep query-critical fields as first-class columns.
- Validate and normalize JSON payloads through schema classes before persistence.

## 4. Core Classes and Responsibilities

### Bootstrap and Infrastructure

- `Pluginora\Core\Bootstrap\Plugin`: main orchestrator.
- `Pluginora\Core\Lifecycle\Activator`: installs tables, seeds defaults, sets db version.
- `Pluginora\Core\Lifecycle\Deactivator`: unschedules events and clears runtime caches.
- `Pluginora\Core\Compatibility\WooCommerceGuard`: dependency and version checks.
- `Pluginora\Core\Compatibility\HposCompatibility`: declares HPOS compatibility.

### Domain Models

- `Rule`: aggregate root for all Pluginora rules.
- `RuleCondition`: condition definition.
- `RuleAction`: action definition.
- `TierDefinition`: quantity pricing tier.
- `DateRange`: normalized site-timezone and GMT window mapper.
- `DiscountResult`: immutable calculation output.
- `CartContext`: normalized cart snapshot used by engines.

### Repositories

- `RuleRepositoryInterface`: fetch/save/delete/duplicate rules.
- `RuleQueryRepositoryInterface`: optimized listing and filtering.
- `CouponRepository`: adapter around `WC_Coupon`.

### Application Services

- `RuleValidator`: validates payloads before persistence.
- `RuleDuplicator`: clones a rule and its child records.
- `RuleScheduler`: activates or deactivates scheduled rules.
- `RuleStatusManager`: explicit status transitions.
- `ConflictResolver`: merges module outputs into a single cart strategy.

### Module Services

- `PricingEngine`: evaluates all dynamic pricing candidates.
- `CouponEngine`: applies or removes coupon outcomes.
- `BogoService`: manages free-product lifecycle.
- `PricingTableRenderer`: frontend tier table renderer.
- `BadgeRenderer`: product badge renderer.

## 5. Hook System

Use registrar classes instead of scattering hooks across services.

Suggested registrars:

- `AdminHooksRegistrar`
- `FrontendHooksRegistrar`
- `CartHooksRegistrar`
- `CouponHooksRegistrar`
- `ScheduleHooksRegistrar`
- `ApiHooksRegistrar`

Each registrar should:

- receive dependencies through constructor injection
- expose `register(): void`
- own only hook wiring, not business logic

### WooCommerce Hooks to Target

- `woocommerce_before_calculate_totals`
- `woocommerce_cart_calculate_fees`
- `woocommerce_before_cart`
- `woocommerce_before_checkout_form`
- `woocommerce_get_price_html`
- `woocommerce_product_get_price`
- `woocommerce_product_variation_get_price`
- `woocommerce_cart_item_price`
- `woocommerce_cart_item_subtotal`
- `woocommerce_sale_flash`
- `woocommerce_applied_coupon`
- `woocommerce_removed_coupon`

### WordPress Hooks to Target

- `admin_menu`
- `admin_enqueue_scripts`
- `wp_ajax_*`
- `rest_api_init`
- custom scheduled event hooks

## 11. HPOS Compatibility Strategy

Pluginora should not store order-related operational data in legacy post assumptions.

Guidelines:

- Declare HPOS compatibility on bootstrap using WooCommerce features utility.
- Use WooCommerce CRUD objects for order interactions.
- Do not join directly against order posts or postmeta for coupon logic.
- Keep Pluginora rule tables independent from order storage engine.
- If usage analytics are added later, store them in Pluginora tables keyed by WooCommerce order IDs only.

## 17. Suggested REST API Structure

Namespace:

- `pluginora/v1`

Endpoints:

- `GET /rules`
- `POST /rules`
- `GET /rules/(?P<id>\d+)`
- `PUT /rules/(?P<id>\d+)`
- `DELETE /rules/(?P<id>\d+)`
- `POST /rules/(?P<id>\d+)/duplicate`
- `POST /rules/(?P<id>\d+)/activate`
- `POST /rules/(?P<id>\d+)/deactivate`
- `GET /products/search`
- `GET /categories/search`
- `GET /coupons/available`
- `POST /settings`
- `GET /settings`

REST should be used for complex admin screens. AJAX can still be used for lightweight selectors where that matches current admin stack choices.