<?php

declare(strict_types=1);

namespace Pluginora\Core\Application;

use Pluginora\Core\Settings\SettingsRepository;
use Pluginora\Modules\CouponEngine\Application\CouponSavingsEstimator;
use Pluginora\Modules\DynamicPricing\Application\CartDiscountService;
use Pluginora\Modules\DynamicPricing\Application\ProductPricingService;
use Pluginora\Support\Rule;
use WC_Cart;

final class ConflictResolver
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly ProductPricingService $productPricingService,
        private readonly CartDiscountService $cartDiscountService,
        private readonly CouponSavingsEstimator $couponSavingsEstimator
    ) {
    }

    public function getMode(): string
    {
        return $this->settingsRepository->getConflictMode();
    }

    public function shouldApplyDynamicPricing(WC_Cart $cart): bool
    {
        $mode = $this->getMode();

        if ('stack_all' === $mode) {
            return true;
        }

        $couponSavings = $this->couponSavingsEstimator->estimateCouponSideSavings($cart);

        if ('coupon_priority' === $mode) {
            return $couponSavings <= 0;
        }

        $dynamicSavings = $this->estimateDynamicPricingSavings($cart);

        return $dynamicSavings > $couponSavings;
    }

    public function shouldApplyCouponRule(WC_Cart $cart, Rule $rule): bool
    {
        $mode = $this->getMode();

        if ('stack_all' === $mode || 'coupon_priority' === $mode) {
            return true;
        }

        $candidateSavings = $this->couponSavingsEstimator->estimateRuleSavings($rule, $cart);

        if ($candidateSavings <= 0) {
            return false;
        }

        return $candidateSavings >= $this->estimateDynamicPricingSavings($cart);
    }

    private function estimateDynamicPricingSavings(WC_Cart $cart): float
    {
        return $this->productPricingService->estimateCartSavings($cart)
            + $this->cartDiscountService->estimateCartSavings($cart);
    }
}
