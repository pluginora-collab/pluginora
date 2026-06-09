# Pluginora E2E Coverage

Pluginora now includes a Playwright-based E2E suite for a real WordPress + WooCommerce site.

## Current browser coverage

1. WordPress admin authentication and Pluginora workspace load.
2. REST-backed rule creation and visibility in the Promotion Library.
3. Simple discount rendering on the product page.
4. Discounted pricing visibility on the cart page after add-to-cart.

## Required environment

Copy the example environment file and then populate or append the real values for your site:

```bash
cp tests/E2E/.env.example tests/E2E/.env.local
```

Required variables:

- `PLUGINORA_E2E_BASE_URL`
- `PLUGINORA_E2E_ADMIN_USERNAME`
- `PLUGINORA_E2E_ADMIN_PASSWORD`
- `PLUGINORA_E2E_PRODUCT_ID`
- `PLUGINORA_E2E_PRODUCT_URL`

Optional variables:

- `PLUGINORA_E2E_CART_URL`
- `PLUGINORA_E2E_CHECKOUT_URL`

## Fixture seeding

Use WP-CLI against a WooCommerce site with Pluginora active:

```bash
wp eval-file tests/E2E/bin/seed-fixtures.php >> tests/E2E/.env.local
```

That command creates or updates a known product fixture and prints environment values you can feed directly into Playwright.

## Local execution

```bash
npm install
npm run e2e:install
npm run e2e
```

## GitHub Actions execution

The repository includes a manual workflow at `.github/workflows/e2e.yml`.

Use it when you want to run the same Playwright suite against a staging site from GitHub Actions. Store the admin credentials as repository secrets:

- `PLUGINORA_E2E_ADMIN_USERNAME`
- `PLUGINORA_E2E_ADMIN_PASSWORD`

## Remaining gaps

The current browser coverage is intentionally small and should be expanded next into tiered pricing, available coupons, BOGO, and conflict-mode scenarios.