# Pluginora Bootstrap Flow

## Initialization Flow

1. `pluginora.php` defines plugin constants and registers activation and deactivation callbacks.
2. The main file loads Composer PSR-4 autoloading from `vendor/autoload.php`.
3. On `before_woocommerce_init`, Pluginora declares HPOS compatibility.
4. On `plugins_loaded` with priority `20`, `PluginFactory` builds the application.
5. `PluginFactory` creates the `PluginContext`, service container, loader, service providers, and module registrations.
6. `Plugin::boot()` runs the WooCommerce compatibility guard.
7. If requirements pass, the plugin loads translations on `init` and the loader boots providers and modules.
8. Admin and frontend providers register only their own hooks, keeping runtime separation clean.

## Boot Responsibilities

- `pluginora.php`: WordPress entrypoint and lifecycle hook boundary.
- `PluginFactory`: object graph assembly.
- `Container`: dependency resolution.
- `Loader`: provider and module registration order.
- `WooCommerceGuard`: dependency and version gating.
- `HposCompatibility`: HPOS declaration.
- `Activator` and `Deactivator`: lifecycle tasks.