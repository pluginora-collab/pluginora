<?php

declare(strict_types=1);

namespace Pluginora\Modules\CouponEngine\Application;

use Pluginora\Repository\Contracts\RuleQueryRepositoryInterface;
use Pluginora\Support\Rule;
use WC_Cart;

final class CouponSavingsEstimator
{
    /**
     * @var Rule[]|null
     */
    private ?array $cachedRules = null;

    public function __construct(
        private readonly RuleQueryRepositoryInterface $ruleQueryRepository,
        private readonly RuleDataAccessor $ruleDataAccessor,
        private readonly CouponRuleMatcher $couponRuleMatcher,
        private readonly NativeCouponSyncService $nativeCouponSyncService
    ) {
    }

    public function estimateCouponSideSavings(WC_Cart $cart): float
    {
        return max(
            $this->estimateAppliedCouponSavings($cart),
            $this->estimateBestAutoCouponSavings($cart),
            $this->estimateBogoSavings($cart)
        );
    }

    public function estimateRuleSavings(Rule $rule, WC_Cart $cart): float
    {
        if ('coupon_engine' !== $rule->getModule() || 'active' !== $rule->getStatus()) {
            return 0.0;
        }

        if (! $this->couponRuleMatcher->isWithinDateWindow($rule, gmdate('Y-m-d H:i:s'))) {
            return 0.0;
        }

        return match ($rule->getRuleType()) {
            'basic_coupon'      => $this->estimateNativeCouponRuleSavings($rule, $cart),
            'auto_apply_coupon' => $this->couponRuleMatcher->matchesAutoApplyRule($rule, $cart)
                ? $this->estimateNativeCouponRuleSavings($rule, $cart)
                : 0.0,
            'bogo_coupon'       => $this->estimateBogoRuleSavings($rule, $cart),
            default             => 0.0,
        };
    }

    public function estimateAppliedCouponSavings(WC_Cart $cart): float
    {
        $savings = 0.0;

        foreach ($cart->get_applied_coupons() as $couponCode) {
            $coupon = $this->nativeCouponSyncService->findCouponByCode((string) $couponCode);

            if (null === $coupon) {
                continue;
            }

            $savings += $this->estimateNativeCouponSavingsFromCoupon($coupon, $cart);
        }

        return $savings;
    }

    public function estimateBestAutoCouponSavings(WC_Cart $cart): float
    {
        $best = 0.0;

        foreach ($this->getActiveRules() as $rule) {
            if ('auto_apply_coupon' !== $rule->getRuleType()) {
                continue;
            }

            $best = max($best, $this->estimateRuleSavings($rule, $cart));
        }

        return $best;
    }

    public function estimateBogoSavings(WC_Cart $cart): float
    {
        $total = 0.0;

        foreach ($this->getActiveRules() as $rule) {
            if ('bogo_coupon' !== $rule->getRuleType()) {
                continue;
            }

            $total += $this->estimateRuleSavings($rule, $cart);
        }

        return $total;
    }

    /**
     * @return Rule[]
     */
    private function getActiveRules(): array
    {
        if (null === $this->cachedRules) {
            $this->cachedRules = $this->ruleQueryRepository->findActiveByModule('coupon_engine');
        }

        return $this->cachedRules;
    }

    private function estimateNativeCouponRuleSavings(Rule $rule, WC_Cart $cart): float
    {
        $discountType = (string) $this->ruleDataAccessor->getActionValue($rule, 'coupon_discount_type', 'percent');

        if ('free_shipping' === $discountType) {
            return 0.0;
        }

        $amount          = (float) $this->ruleDataAccessor->getActionValue($rule, 'coupon_amount', 0);
        $eligibleSubtotal = $this->getEligibleSubtotal($rule, $cart);

        if ($eligibleSubtotal <= 0 || $amount <= 0) {
            return 0.0;
        }

        if ('fixed_cart' === $discountType) {
            return min($eligibleSubtotal, $amount);
        }

        return min($eligibleSubtotal, ($eligibleSubtotal * $amount) / 100);
    }

    private function estimateBogoRuleSavings(Rule $rule, WC_Cart $cart): float
    {
        $rewardQuantity  = $this->couponRuleMatcher->getApplicableRewardQuantity($rule, $cart);
        $rewardProductIds = $this->ruleDataAccessor->getItemIds($rule, 'get_product');

        if ($rewardQuantity <= 0 || [] === $rewardProductIds) {
            return 0.0;
        }

        $product = wc_get_product((int) $rewardProductIds[0]);

        if (! $product) {
            return 0.0;
        }

        $basePrice    = (float) $product->get_price();
        $rewardType   = (string) $this->ruleDataAccessor->getActionValue($rule, 'reward_type', 'free');
        $rewardAmount = (float) $this->ruleDataAccessor->getActionValue($rule, 'discount_value', 0);

        if ('percentage' === $rewardType) {
            return ($basePrice * $rewardQuantity * $rewardAmount) / 100;
        }

        return $basePrice * $rewardQuantity;
    }

    private function estimateNativeCouponSavingsFromCoupon(\WC_Coupon $coupon, WC_Cart $cart): float
    {
        $subtotal = $this->getEligibleSubtotalForCoupon($coupon, $cart);
        $amount   = (float) $coupon->get_amount();

        if ($subtotal <= 0 || $amount <= 0) {
            return 0.0;
        }

        return match ($coupon->get_discount_type()) {
            'fixed_cart'    => min($subtotal, $amount),
            'percent'       => min($subtotal, ($subtotal * $amount) / 100),
            'fixed_product' => $this->estimateFixedProductSavings($coupon, $cart),
            default         => 0.0,
        };
    }

    private function estimateFixedProductSavings(\WC_Coupon $coupon, WC_Cart $cart): float
    {
        $savings = 0.0;
        $amount = (float) $coupon->get_amount();
        $productTargets  = array_map('intval', $coupon->get_product_ids());
        $categoryTargets = array_map('intval', $coupon->get_product_categories());

        foreach ($cart->get_cart() as $cartItem) {
            if (empty($cartItem['data']) || ! is_object($cartItem['data'])) {
                continue;
            }

            $product   = $cartItem['data'];
            $productId = (int) $product->get_id();
            $parentId  = method_exists($product, 'get_parent_id') ? (int) $product->get_parent_id() : 0;
            $targetId  = $parentId > 0 ? $parentId : $productId;

            $eligible = false;
            if ([] === $productTargets && [] === $categoryTargets) {
                $eligible = true;
            } elseif ([] !== $productTargets && in_array($targetId, $productTargets, true)) {
                $eligible = true;
            } elseif ([] !== $categoryTargets) {
                $categoryIds = wc_get_product_term_ids($targetId, 'product_cat');
                if ([] !== array_intersect(array_map('intval', $categoryIds), $categoryTargets)) {
                    $eligible = true;
                }
            }

            if ($eligible) {
                $itemSubtotal = ((float) $product->get_price('edit')) * (int) $cartItem['quantity'];
                $itemSavings = $amount * (int) $cartItem['quantity'];
                $savings += min($itemSubtotal, $itemSavings);
            }
        }

        return $savings;
    }

    private function getEligibleSubtotal(Rule $rule, WC_Cart $cart): float
    {
        $productTargets  = $this->ruleDataAccessor->getItemIds($rule, 'product');
        $categoryTargets = $this->ruleDataAccessor->getItemIds($rule, 'category');

        if ([] === $productTargets && [] === $categoryTargets) {
            return (float) $cart->get_subtotal();
        }

        $subtotal = 0.0;

        foreach ($cart->get_cart() as $cartItem) {
            if (empty($cartItem['data']) || ! is_object($cartItem['data'])) {
                continue;
            }

            $product   = $cartItem['data'];
            $productId = (int) $product->get_id();
            $parentId  = method_exists($product, 'get_parent_id') ? (int) $product->get_parent_id() : 0;
            $targetId  = $parentId > 0 ? $parentId : $productId;

            if ([] !== $productTargets && in_array($targetId, $productTargets, true)) {
                $subtotal += ((float) $product->get_price('edit')) * (int) $cartItem['quantity'];
                continue;
            }

            if ([] !== $categoryTargets) {
                $categoryIds = wc_get_product_term_ids($targetId, 'product_cat');

                if ([] !== array_intersect(array_map('intval', $categoryIds), $categoryTargets)) {
                    $subtotal += ((float) $product->get_price('edit')) * (int) $cartItem['quantity'];
                }
            }
        }

        return $subtotal;
    }

    private function getEligibleSubtotalForCoupon(\WC_Coupon $coupon, WC_Cart $cart): float
    {
        $productTargets  = array_map('intval', $coupon->get_product_ids());
        $categoryTargets = array_map('intval', $coupon->get_product_categories());

        if ([] === $productTargets && [] === $categoryTargets) {
            return (float) $cart->get_subtotal();
        }

        $subtotal = 0.0;

        foreach ($cart->get_cart() as $cartItem) {
            if (empty($cartItem['data']) || ! is_object($cartItem['data'])) {
                continue;
            }

            $product   = $cartItem['data'];
            $productId = (int) $product->get_id();
            $parentId  = method_exists($product, 'get_parent_id') ? (int) $product->get_parent_id() : 0;
            $targetId  = $parentId > 0 ? $parentId : $productId;

            if ([] !== $productTargets && in_array($targetId, $productTargets, true)) {
                $subtotal += ((float) $product->get_price('edit')) * (int) $cartItem['quantity'];
                continue;
            }

            if ([] !== $categoryTargets) {
                $categoryIds = wc_get_product_term_ids($targetId, 'product_cat');

                if ([] !== array_intersect(array_map('intval', $categoryIds), $categoryTargets)) {
                    $subtotal += ((float) $product->get_price('edit')) * (int) $cartItem['quantity'];
                }
            }
        }

        return $subtotal;
    }
}
