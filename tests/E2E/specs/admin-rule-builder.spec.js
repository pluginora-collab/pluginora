import { test, expect } from '@playwright/test';
import {
    buildSimpleDiscountPayload,
    createRuleViaRest,
    deleteRuleViaRest,
    openPluginoraWorkspace,
    uniqueRuleName,
} from '../helpers/pluginora.js';

test('merchant can load the Pluginora workspace and see a saved rule', async ({ page }) => {
    await openPluginoraWorkspace(page);

    const ruleName = uniqueRuleName('E2E Admin Discount');
    const rule = await createRuleViaRest(page, buildSimpleDiscountPayload({ name: ruleName }));

    try {
        await page.reload();
        await expect(page.getByRole('heading', { name: 'Promotion Library' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Promotion Policy' })).toBeVisible();
        await expect(page.getByText(ruleName)).toBeVisible();
    } finally {
        await deleteRuleViaRest(page, rule.id);
    }
});