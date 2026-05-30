<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Application;

use Pluginora\Modules\DynamicPricing\Domain\ProductDiscount;
use Pluginora\Repository\Contracts\RuleQueryRepositoryInterface;
use Pluginora\Support\Rule;
use WC_Cart;
use WC_Product;

final class ProductPricingService
{
    /**
     * @var Rule[]|null
     */
    private ?array $cachedRules = null;

    public function __construct(
        private readonly RuleQueryRepositoryInterface $ruleQueryRepository,
        private readonly RuleMatcher $ruleMatcher,
        private readonly RuleDataAccessor $ruleDataAccessor
    ) {
    }

    public function resolveProductDiscount(
        WC_Product $product,
        int $quantity = 1,
        ?float $basePrice = null
    ): ?ProductDiscount {
        $originalPrice = null !== $basePrice ? $basePrice : (float) $product->get_price();

        if ($originalPrice <= 0) {
            return null;
        }

        $bestDiscount = null;

        foreach ($this->getActiveRules() as $rule) {
            if (! in_array($rule->getRuleType(), ['simple_discount', 'tiered_pricing'], true)) {
                continue;
            }

            if (! $this->ruleMatcher->matchesProduct($rule, $product)) {
                continue;
            }

            $discount = $this->createDiscountForRule($rule, $originalPrice, $quantity);

            if (null === $discount) {
                continue;
            }

            if (null === $bestDiscount || $discount->getDiscountedPrice() < $bestDiscount->getDiscountedPrice()) {
                $bestDiscount = $discount;
            }
        }

        return $bestDiscount;
    }

    public function estimateCartSavings(WC_Cart $cart): float
    {
        $savings = 0.0;

        foreach ($cart->get_cart() as $cartItem) {
            if (empty($cartItem['data']) || ! is_object($cartItem['data'])) {
                continue;
            }

            $product       = $cartItem['data'];
            $originalPrice = isset($cartItem['pluginora_original_price'])
                ? (float) $cartItem['pluginora_original_price']
                : (float) $product->get_price('edit');
            $discount      = $this->resolveProductDiscount(
                $product,
                (int) $cartItem['quantity'],
                $originalPrice
            );

            if (null === $discount) {
                continue;
            }

            $savings += $discount->getSavingsAmount() * (int) $cartItem['quantity'];
        }

        return $savings;
    }

    public function getTieredPricingRule(WC_Product $product): ?Rule
    {
        foreach ($this->getActiveRules() as $rule) {
            if ('tiered_pricing' !== $rule->getRuleType()) {
                continue;
            }

            if (! $this->ruleMatcher->matchesProduct($rule, $product)) {
                continue;
            }

            if (! $this->ruleDataAccessor->getActionValue($rule, 'show_pricing_table', false)) {
                continue;
            }

            return $rule;
        }

        return null;
    }

    /**
     * @return Rule[]
     */
    private function getActiveRules(): array
    {
        if (null === $this->cachedRules) {
            $this->cachedRules = $this->ruleQueryRepository->findActiveByModule('dynamic_pricing');
        }

        return $this->cachedRules;
    }

    private function createDiscountForRule(Rule $rule, float $originalPrice, int $quantity): ?ProductDiscount
    {
        if ('simple_discount' === $rule->getRuleType()) {
            $discountType  = (string) $this->ruleDataAccessor->getActionValue($rule, 'discount_type', 'percentage');
            $discountValue = (float) $this->ruleDataAccessor->getActionValue($rule, 'discount_value', 0);
        } else {
            $tier = $this->findMatchingTier($rule, $quantity);

            if (null === $tier) {
                return null;
            }

            $discountType  = $tier['discount_type'];
            $discountValue = $tier['discount_value'];
        }

        $discountedPrice = $this->calculateDiscountedPrice($originalPrice, $discountType, $discountValue);

        if ($discountedPrice >= $originalPrice) {
            return null;
        }

        return new ProductDiscount(
            $rule,
            $originalPrice,
            $discountedPrice,
            $discountType,
            $discountValue,
            (bool) $this->ruleDataAccessor->getActionValue($rule, 'badge_enabled', false),
            (string) $this->ruleDataAccessor->getActionValue($rule, 'badge_text', ''),
            (bool) $this->ruleDataAccessor->getActionValue($rule, 'savings_message_enabled', false)
        );
    }

    private function calculateDiscountedPrice(float $originalPrice, string $discountType, float $discountValue): float
    {
        if ('fixed' === $discountType) {
            return max(0.0, $originalPrice - $discountValue);
        }

        return max(0.0, $originalPrice - (($originalPrice * $discountValue) / 100));
    }

    private function findMatchingTier(Rule $rule, int $quantity): ?array
    {
        foreach ($rule->getTiers() as $tier) {
            $maxQuantity = $tier->getMaxQuantity();

            if ($quantity < $tier->getMinQuantity()) {
                continue;
            }

            if (null !== $maxQuantity && $quantity > $maxQuantity) {
                continue;
            }

            return [
                'discount_type'  => $tier->getDiscountType(),
                'discount_value' => $tier->getDiscountValue(),
            ];
        }

        return null;
    }
}
