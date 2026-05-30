# Pluginora Architecture Overview

## 1. Recommended Plugin Architecture

Pluginora should use a layered, modular architecture built around service composition instead of feature-specific procedural hooks.

Recommended layers:

- Bootstrap layer: plugin entrypoint, dependency checks, service registration, lifecycle hooks.
- Core layer: container, configuration, logging abstraction, lifecycle manager, module loader.
- Domain layer: rule entities, value objects, repositories, validators, condition evaluation contracts.
- Application layer: pricing engine, coupon engine, conflict resolver, scheduling coordinator, admin use cases.
- Infrastructure layer: custom table persistence, WooCommerce adapters, WP Cron adapter, AJAX endpoints, REST controllers.
- Presentation layer: admin screens, field schemas, frontend renderers, cart notices, pricing tables, badges.

This keeps WooCommerce and WordPress as integration boundaries, not as the center of business logic.

## 2. Folder Structure

```text
pluginora/
├── pluginora.php
├── uninstall.php
├── readme.txt
├── composer.json
├── languages/
│   └── pluginora.pot
├── assets/
│   ├── admin/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── frontend/
│       ├── css/
│       ├── js/
│       └── images/
├── templates/
│   └── frontend/
├── src/
│   ├── Admin/
│   │   ├── Ajax/
│   │   ├── Api/
│   │   ├── Pages/
│   │   ├── Settings/
│   │   ├── Assets/
│   │   ├── Forms/
│   │   └── Notices/
│   ├── Core/
│   │   ├── Bootstrap/
│   │   ├── Container/
│   │   ├── Contracts/
│   │   ├── Support/
│   │   ├── Lifecycle/
│   │   └── Compatibility/
│   ├── Frontend/
│   │   ├── Assets/
│   │   ├── Cart/
│   │   ├── Pricing/
│   │   ├── Notices/
│   │   └── Templates/
│   ├── Modules/
│   │   ├── DynamicPricing/
│   │   │   ├── Application/
│   │   │   ├── Domain/
│   │   │   ├── Infrastructure/
│   │   │   └── Presentation/
│   │   └── CouponEngine/
│   │       ├── Application/
│   │       ├── Domain/
│   │       ├── Infrastructure/
│   │       └── Presentation/
│   ├── Repository/
│   ├── Rest/
│   └── Support/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── E2E/
└── docs/
    └── architecture/
```

## 3. Module Boundaries

Dynamic Pricing module owns:

- pricing rules
- quantity tiers
- cart subtotal rules
- sale badges
- strike prices
- savings messaging
- scheduled activation

Coupon Engine module owns:

- native WooCommerce coupon orchestration
- auto-apply conditions
- BOGO orchestration
- coupon discovery widgets

Shared services in Core/Application own:

- conflict resolution
- rule evaluation contracts
- cart context normalization
- schedule state transitions
- HPOS compatibility adapters

## 4. Recommended Tech Stack

- PHP 8.1+ minimum for enums-like patterns, readonly-style discipline, and stronger typing.
- Composer for PSR-4 autoloading and development tooling.
- WooCommerce admin UI patterns with progressive enhancement.
- Vanilla ES modules or React for admin rule builder. Recommendation: React with `@wordpress/scripts` for maintainability.
- Custom REST API endpoints for complex admin interactions.
- WordPress Settings API for global plugin settings only.
- Custom admin pages for rule CRUD instead of trying to force complex repeaters into Settings API tables.
- Action Scheduler if available through WooCommerce for reliable deferred jobs; otherwise WP Cron fallback.

## 5. Suggested Naming Conventions

- Namespace root: `Pluginora`.
- Class names: singular, descriptive, noun-based where possible.
- Service suffixes: `Service`, `Manager`, `Resolver`, `Repository`, `Controller`, `Renderer`, `Registrar`.
- Interfaces: prefix with `Contract` only when domain meaning is weak; otherwise use natural names like `RuleRepositoryInterface`.
- Hook registration classes: `...Hooks` or `...Registrar`.
- Value objects: `Money`, `DateRange`, `RuleScope`, `DiscountResult`, `CartContext`.
- Database tables: `{$wpdb->prefix}pluginora_rules`, `{$wpdb->prefix}pluginora_rule_conditions`, `{$wpdb->prefix}pluginora_rule_actions`, `{$wpdb->prefix}pluginora_rule_usage_logs`.
- Option keys: `pluginora_settings`, `pluginora_db_version`.

## 6. Example Bootstrap Skeleton

```php
<?php

declare(strict_types=1);

namespace Pluginora\Core\Bootstrap;

final class Plugin
{
    public function boot(): void
    {
        // 1. Check WooCommerce dependency.
        // 2. Register autoloader/container services.
        // 3. Register lifecycle hooks.
        // 4. Boot modules.
        // 5. Register admin/frontend integrations.
    }
}
```

## 7. Architecture Decisions

- Use custom tables for rule definitions because rule querying, tier storage, priorities, and scheduling are not a good fit for post meta at scale.
- Use native WooCommerce coupon posts for actual coupons to preserve compatibility with reports, third-party tools, and existing WooCommerce flows.
- Keep rule evaluation stateless where possible so pricing calculation can be rerun safely on cart refresh.
- Use DTOs and value objects between storage and engine layers to isolate WooCommerce data structures from business logic.