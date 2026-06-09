import { expect } from '@playwright/test';

export function requireEnv(name) {
    const value = process.env[name];

    if (! value) {
        throw new Error(`Missing required environment variable: ${name}`);
    }

    return value;
}

export function uniqueRuleName(prefix) {
    return `${prefix} ${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

export async function openPluginoraWorkspace(page) {
    await page.goto('/wp-admin/admin.php?page=pluginora');
    await expect(page.getByRole('heading', { name: 'Pluginora', exact: true })).toBeVisible();
    await page.waitForFunction(() => Boolean(window.pluginoraAdmin?.restBase && window.pluginoraAdmin?.nonce));
}

export function buildSimpleDiscountPayload(overrides = {}) {
    const productId = Number(overrides.productId ?? requireEnv('PLUGINORA_E2E_PRODUCT_ID'));
    const { productId: omittedProductId, ...remainingOverrides } = overrides;

    if (! Number.isInteger(productId) || productId <= 0) {
        throw new Error('PLUGINORA_E2E_PRODUCT_ID must be a positive integer.');
    }

    void omittedProductId;

    return {
        module: 'dynamic_pricing',
        rule_type: 'simple_discount',
        name: uniqueRuleName('E2E Simple Discount'),
        status: 'active',
        priority: 1,
        discount_type: 'percentage',
        discount_value: 10,
        applies_to: 'selected_products',
        selected_products: [productId],
        selected_categories: [],
        excluded_products: [],
        badge_enabled: true,
        badge_text: 'E2E Sale',
        savings_message_enabled: true,
        ...remainingOverrides,
    };
}

async function getRestContext(page) {
    return page.evaluate(() => ({
        nonce: window.pluginoraAdmin.nonce,
        restBase: window.pluginoraAdmin.restBase,
    }));
}

export async function createRuleViaRest(page, payload) {
    const { nonce, restBase } = await getRestContext(page);
    const result = await page.evaluate(
        async ({ requestNonce, requestPayload, requestRestBase }) => {
            const response = await fetch(`${requestRestBase}rules`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': requestNonce,
                },
                body: JSON.stringify(requestPayload),
            });

            return {
                body: await response.json(),
                status: response.status,
            };
        },
        {
            requestNonce: nonce,
            requestPayload: payload,
            requestRestBase: restBase,
        }
    );

    if (result.status < 200 || result.status >= 300) {
        throw new Error(`Pluginora rule creation failed: ${JSON.stringify(result.body)}`);
    }

    return result.body.item;
}

export async function deleteRuleViaRest(page, ruleId) {
    const { nonce, restBase } = await getRestContext(page);

    await page.evaluate(
        async ({ requestNonce, requestRestBase, requestRuleId }) => {
            await fetch(`${requestRestBase}rules/${requestRuleId}`, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': requestNonce,
                },
            });
        },
        {
            requestNonce: nonce,
            requestRestBase: restBase,
            requestRuleId: ruleId,
        }
    );
}