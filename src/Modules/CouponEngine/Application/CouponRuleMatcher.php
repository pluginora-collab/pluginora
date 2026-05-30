<?php

declare(strict_types=1);

namespace Pluginora\Modules\CouponEngine\Application;

use Pluginora\Support\Rule;
use WC_Cart;

final class CouponRuleMatcher
{
    public function __construct(private readonly RuleDataAccessor $ruleDataAccessor)
    {
    }

    public function matchesAutoApplyRule(Rule $rule, WC_Cart $cart): bool
    {
        $subtotal = (float) $cart->get_subtotal();
        $minimum  = (float) $this->ruleDataAccessor->getConditionValue($rule, 'min_cart_amount', 0);
        $maximum  = $this->ruleDataAccessor->getConditionValue($rule, 'max_cart_amount');

        if ($subtotal < $minimum) {
            return false;
        }

        if (null !== $maximum && '' !== $maximum && $subtotal > (float) $maximum) {
            return false;
        }

        return $this->matchesProductsAndCategories($rule, $cart);
    }

    public function getBogoQualifiedQuantity(Rule $rule, WC_Cart $cart): int
    {
        $buyProductIds = $this->ruleDataAccessor->getItemIds($rule, 'buy_product');

        if ([] === $buyProductIds) {
            return 0;
        }

        $buyProductId = (int) $buyProductIds[0];
        $quantity     = 0;

        foreach ($cart->get_cart() as $cartItem) {
            if (! empty($cartItem['pluginora_bogo_rule_id'])) {
                continue;
            }

            $productId = $this->getEffectiveProductId($cartItem);

            if ($productId === $buyProductId) {
                $quantity += (int) $cartItem['quantity'];
            }
        }

        return $quantity;
    }

    public function getApplicableRewardQuantity(Rule $rule, WC_Cart $cart): int
    {
        $buyQuantity = (int) $this->ruleDataAccessor->getConditionValue($rule, 'buy_quantity', 1);

        if ($buyQuantity <= 0) {
            return 0;
        }

        return (int) floor($this->getBogoQualifiedQuantity($rule, $cart) / $buyQuantity);
    }

    public function isWithinDateWindow(Rule $rule, string $timestamp): bool
    {
        $startsAt = $rule->getStartsAtGmt();
        $endsAt   = $rule->getEndsAtGmt();

        if (! empty($startsAt) && $timestamp < $startsAt) {
            return false;
        }

        if (! empty($endsAt) && $timestamp > $endsAt) {
            return false;
        }

        return true;
    }

    private function matchesProductsAndCategories(Rule $rule, WC_Cart $cart): bool
    {
        $productTargets  = $this->ruleDataAccessor->getItemIds($rule, 'product');
        $categoryTargets = $this->ruleDataAccessor->getItemIds($rule, 'category');

        if ([] === $productTargets && [] === $categoryTargets) {
            return true;
        }

        foreach ($cart->get_cart() as $cartItem) {
            $productId   = $this->getEffectiveProductId($cartItem);
            $categoryIds = wc_get_product_term_ids($productId, 'product_cat');

            if ([] !== $productTargets && in_array($productId, $productTargets, true)) {
                return true;
            }

            if (
                [] !== $categoryTargets
                && [] !== array_intersect(array_map('intval', $categoryIds), $categoryTargets)
            ) {
                return true;
            }
        }

        return false;
    }

    private function getEffectiveProductId(array $cartItem): int
    {
        if (empty($cartItem['data']) || ! is_object($cartItem['data'])) {
            return 0;
        }

        $product  = $cartItem['data'];
        $parentId = method_exists($product, 'get_parent_id') ? (int) $product->get_parent_id() : 0;

        return $parentId > 0 ? $parentId : (int) $product->get_id();
    }
}
