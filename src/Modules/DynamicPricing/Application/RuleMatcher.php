<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Application;

use Pluginora\Support\Rule;
use WC_Product;

final class RuleMatcher
{
    public function __construct(private readonly RuleDataAccessor $ruleDataAccessor)
    {
    }

    public function matchesProduct(Rule $rule, WC_Product $product): bool
    {
        $productId        = $this->getEffectiveProductId($product);
        $excludedProducts = $this->ruleDataAccessor->getItemIds($rule, 'excluded_product');

        if (in_array($productId, $excludedProducts, true)) {
            return false;
        }

        $appliesTo = (string) $this->ruleDataAccessor->getConditionValue($rule, 'applies_to', 'all_products');

        if ('all_products' === $appliesTo) {
            return true;
        }

        if ('selected_products' === $appliesTo) {
            return in_array($productId, $this->ruleDataAccessor->getItemIds($rule, 'product'), true);
        }

        if ('selected_categories' === $appliesTo) {
            $categoryIds       = $this->getCategoryIds($product);
            $selectedCategories = $this->ruleDataAccessor->getItemIds($rule, 'category');

            return [] !== array_intersect($categoryIds, $selectedCategories);
        }

        return false;
    }

    public function matchesCartSubtotal(Rule $rule, float $subtotal): bool
    {
        $minAmount = (float) $this->ruleDataAccessor->getConditionValue($rule, 'min_cart_amount', 0);
        $maxAmount = $this->ruleDataAccessor->getConditionValue($rule, 'max_cart_amount');

        if ($subtotal < $minAmount) {
            return false;
        }

        if (null !== $maxAmount && '' !== $maxAmount && $subtotal > (float) $maxAmount) {
            return false;
        }

        return true;
    }

    /**
     * @return int[]
     */
    private function getCategoryIds(WC_Product $product): array
    {
        $productId = $this->getEffectiveProductId($product);

        return array_map('intval', wc_get_product_term_ids($productId, 'product_cat'));
    }

    private function getEffectiveProductId(WC_Product $product): int
    {
        $parentId = $product->get_parent_id();

        return $parentId > 0 ? $parentId : $product->get_id();
    }
}
