# Pluginora Roadmap and Scope

## 15. Step-by-Step Development Roadmap

### Phase 1: Foundation

1. Create plugin bootstrap, autoloading, dependency guard, activation routines, and base folder structure.
2. Build database installer and repository layer for Pluginora rule tables.
3. Register HPOS compatibility and lifecycle hooks.
4. Add coding standards, testing scaffold, and asset build tooling.

### Phase 2: Admin Core

1. Build rule list page and guided rule builder shell.
2. Add REST endpoints for rules, settings, product search, and category search.
3. Implement rule validation, save, edit, duplicate, activate, deactivate, and delete flows.

### Phase 3: Dynamic Pricing MVP

1. Implement percentage and fixed amount rules.
2. Implement selected product and selected category scoping.
3. Implement strike prices on shop, product, and cart.
4. Implement scheduled activation and exclusion support.

### Phase 4: Pricing Expansion

1. Implement tiered quantity pricing and pricing tables.
2. Implement cart subtotal discounts and progress notices.
3. Implement sale badge customization and savings messages.

### Phase 5: Coupon Engine MVP

1. Implement native coupon creation and sync.
2. Implement auto-apply conditions for subtotal, selected products, and categories.
3. Implement available coupon displays.

### Phase 6: Coupon Expansion

1. Implement BOGO flows with auto-add and auto-remove.
2. Harden edge cases for mixed carts and reward quantities.

### Phase 7: Hardening

1. Add performance caching.
2. Add structured logging and debug tooling.
3. Add regression tests for conflict strategies.
4. Validate against latest WooCommerce and HPOS environments.

## 16. MVP vs Future Scope Separation

### MVP

- percentage discounts
- fixed discounts
- product and category targeting
- excluded products
- scheduled rules
- strike price rendering
- cart subtotal discount
- basic progress and unlocked notices
- native coupon creation
- auto-apply coupon rules for subtotal and product or category conditions
- available coupons display toggles
- conflict resolver with three modes
- duplicate, activate, deactivate, edit, and delete rule actions

### Future Scope

- advanced condition groups with nested AND or OR logic
- customer segment rules by role, history, or location
- usage analytics dashboard
- import and export tooling
- rule simulation mode
- A/B testing for promotions
- multi-currency support policies
- template override system
- per-rule audit timeline
- enterprise conflict strategies and exclusions

## Recommended Delivery Order for This Build

1. Foundation scaffold
2. Admin rule builder shell
3. Dynamic pricing core
4. Coupon engine core
5. Conflict resolver hardening
6. Frontend polish and notices
7. Test coverage and release preparation

## Release Quality Gates

- PHPCS passes with WordPress ruleset.
- PHPStan or equivalent static analysis passes at agreed level.
- Unit coverage for rule evaluators and conflict strategies.
- Integration coverage for cart price application.
- Manual QA against HPOS-enabled WooCommerce.
- Translation strings extracted and domain verified.