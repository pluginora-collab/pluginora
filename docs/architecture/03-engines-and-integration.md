# Pluginora Engines and Integration Design

## 6. Pricing Engine Design

The pricing engine should be a pipeline, not a monolith.

### Evaluation Flow

1. Build a normalized `CartContext` and `CatalogContext`.
2. Load active rules for the current timestamp.
3. Filter rules by module and rule type.
4. Evaluate conditions against the context.
5. Produce candidate `DiscountResult` objects.
6. Send results to the `ConflictResolver`.
7. Apply final pricing adjustments through WooCommerce hooks.
8. Expose renderer data for strike prices, badges, notices, and savings messages.

### Rule Evaluators

Use one evaluator per rule type:

- `PercentageDiscountEvaluator`
- `FixedDiscountEvaluator`
- `TieredPricingEvaluator`
- `CartSubtotalDiscountEvaluator`
- `ScheduledRuleEvaluator` as a date gate, not a pricing evaluator

Common contract:

```php
<?php

namespace Pluginora\Modules\DynamicPricing\Application;

use Pluginora\Support\CartContext;
use Pluginora\Support\DiscountResult;
use Pluginora\Support\Rule;

interface RuleEvaluatorInterface
{
    public function supports(Rule $rule): bool;

    public function evaluate(Rule $rule, CartContext $context): ?DiscountResult;
}
```

### Frontend Price Presentation

Keep calculation separate from display.

- Engine computes adjusted values.
- Presentation layer decides how to render strike prices and savings labels.
- Cart and catalog display should pull from a shared pricing presenter to avoid drift.

## 7. Coupon Engine Design

Couponora should treat WooCommerce coupons as the source of truth for actual coupon application.

### Responsibilities

- Create and update native WooCommerce coupons.
- Detect cart conditions for auto-apply rules.
- Apply and remove coupons idempotently.
- Manage BOGO companion cart items.
- Surface available coupons on cart, checkout, and account pages.

### Core Components

- `CouponSyncService`: creates or updates `WC_Coupon` records when admin saves native-backed coupon rules.
- `AutoApplyCouponEvaluator`: determines whether a coupon should be auto-applied.
- `AutoApplyCouponManager`: applies or removes coupon codes.
- `BogoEvaluator`: validates buy/get conditions.
- `BogoCartManager`: inserts, tags, prices, and removes reward line items.
- `AvailableCouponProvider`: frontend coupon availability service.

### BOGO Handling

Avoid hidden side effects in generic pricing hooks.

- Add reward items with deterministic cart item metadata.
- Revalidate reward items on cart load and quantity changes.
- Remove reward items immediately if buy conditions fail.
- Keep BOGO logic isolated from simple percentage and fixed discount evaluators.

## 8. Conflict Resolution Architecture

This is the core differentiator of the plugin and should live in a shared application service.

### Supported Modes

- `stack_all`
- `best_discount_only`
- `coupon_priority`

Default mode:

- `best_discount_only`

### Resolver Inputs

- candidate pricing results
- candidate coupon results
- store-wide conflict settings
- per-rule priority values
- exclusivity flags in future versions

### Resolver Output

- final line-item adjustments
- final coupon actions
- frontend messaging payload
- audit metadata for debugging

### Decision Rules

- In `best_discount_only`, compare net customer savings per line item or cart context before applying.
- In `coupon_priority`, preserve valid coupons first, then only apply non-conflicting pricing adjustments.
- In `stack_all`, still enforce safety constraints so the effective price never drops below zero.

### Extensibility

Create a strategy contract:

```php
<?php

namespace Pluginora\Core\Contracts;

use Pluginora\Support\ConflictContext;
use Pluginora\Support\ResolvedDiscountSet;

interface ConflictStrategyInterface
{
    public function resolve(ConflictContext $context): ResolvedDiscountSet;
}
```

That allows future enterprise policies without rewriting engines.

## 13. Performance Optimization Strategy

- Cache active rule IDs by date window and status.
- Cache product-to-rule lookups with invalidation on rule save.
- Avoid loading full rule graphs unless the cart context can actually match them.
- Pre-index selected product and category relations in join tables.
- Run cart recalculations idempotently and avoid repeated heavy queries inside `woocommerce_before_calculate_totals`.
- Use lazy service instantiation in admin-only and frontend-only contexts.
- Prefer batch reads over per-line-item database fetches.
- Expose internal debug logging behind a disabled-by-default setting.

## 14. Recommended Tech Stack

Implementation recommendation:

- PHP 8.1+
- WooCommerce latest stable
- WordPress latest stable
- Composer PSR-4 autoloading
- `@wordpress/scripts` for admin build pipeline
- React for guided rule builder UI
- PHPUnit and WooCommerce integration tests
- Playwright or Cypress for key admin and storefront E2E flows

## 20. Example Implementation Skeletons

### Conflict Resolver Skeleton

```php
<?php

declare(strict_types=1);

namespace Pluginora\Core\Application;

use Pluginora\Core\Contracts\ConflictStrategyInterface;
use Pluginora\Support\ConflictContext;
use Pluginora\Support\ResolvedDiscountSet;

final class ConflictResolver
{
    public function __construct(
        private ConflictStrategyInterface $strategy
    ) {
    }

    public function resolve(ConflictContext $context): ResolvedDiscountSet
    {
        return $this->strategy->resolve($context);
    }
}
```

### Rule Repository Skeleton

```php
<?php

declare(strict_types=1);

namespace Pluginora\Repository;

use Pluginora\Support\Rule;

interface RuleRepositoryInterface
{
    public function find(int $ruleId): ?Rule;

    public function save(Rule $rule): int;

    public function delete(int $ruleId): bool;

    public function duplicate(int $ruleId): int;
}
```