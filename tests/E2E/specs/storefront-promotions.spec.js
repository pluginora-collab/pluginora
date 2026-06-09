import { test, expect } from '@playwright/test';
import {
    buildSimpleDiscountPayload,
    createRuleViaRest,
    deleteRuleViaRest,
    openPluginoraWorkspace,
    requireEnv,
    uniqueRuleName,
} from '../helpers/pluginora.js';

test('shopper sees Pluginora pricing on product and cart pages', async ({ page }) => {
    await openPluginoraWorkspace(page);

    const productUrl = requireEnv('PLUGINORA_E2E_PRODUCT_URL');
    const cartUrl = process.env.PLUGINORA_E2E_CART_URL || '/cart/';
    const rule = await createRuleViaRest(
        page,
        buildSimpleDiscountPayload({
            badge_text: 'E2E Sale',
            name: uniqueRuleName('E2E Storefront Discount'),
        })
    );

    try {
        await page.goto(productUrl);
        await expect(page.locator('.pluginora-price')).toBeVisible();
        await expect(page.locator('.pluginora-onsale')).toContainText('E2E Sale');
        await expect(page.locator('.pluginora-savings-message')).toBeVisible();

        const addToCartButton = page.locator('form.cart button.single_add_to_cart_button').first();
        await expect(addToCartButton).toBeVisible();
        await addToCartButton.click();

        await page.goto(cartUrl);
        await expect(page.locator('.pluginora-cart-price')).toBeVisible();
    } finally {
        await deleteRuleViaRest(page, rule.id);
    }
});