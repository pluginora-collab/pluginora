# Pluginora Performance Profiling

Pluginora now ships with a repeatable storefront profiling script for product, cart, and checkout URLs.

## Goal

Measure the cost of Pluginora rules on the exact store stack instead of relying on assumptions.

## Recommended scenarios

Run each scenario on staging with caches warmed and then after a cache purge:

1. Product page with one active simple discount rule.
2. Product page with tiered pricing enabled.
3. Cart page with cart subtotal discount messaging.
4. Cart page with an auto-apply coupon rule.
5. Cart page with mixed active rules and the target conflict mode.
6. Checkout page with the same mixed cart.

## Command

```bash
bin/profile-storefront.sh https://example.test/product/sample/ https://example.test/cart/ https://example.test/checkout/
```

## Environment variables

- `PLUGINORA_PROFILE_ITERATIONS`: number of timed requests per URL. Default: `5`.

## What to capture

- average response time
- minimum response time
- maximum response time
- whether the page remained functionally correct under the profiled rule mix

## Notes

- This script is intentionally simple and curl-based so it runs anywhere.
- Use it as a first-pass timing tool, then follow up with APM or host-level profiling if timings are borderline.
- Profiling is still not complete until the script is executed against the real staging environment.