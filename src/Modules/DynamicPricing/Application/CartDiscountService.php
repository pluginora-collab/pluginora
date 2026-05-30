<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Application;

use Pluginora\Modules\DynamicPricing\Domain\CartDiscount;
use Pluginora\Repository\Contracts\RuleQueryRepositoryInterface;
use Pluginora\Support\Rule;
use WC_Cart;

final class CartDiscountService
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

    public function resolveCartDiscount(WC_Cart $cart): ?CartDiscount
    {
        $subtotal = (float) $cart->get_subtotal();

        if ($subtotal <= 0) {
            return null;
        }

        $bestDiscount = null;

        foreach ($this->getActiveRules() as $rule) {
            if ('cart_subtotal_discount' !== $rule->getRuleType()) {
                continue;
            }

            if (! $this->ruleMatcher->matchesCartSubtotal($rule, $subtotal)) {
                continue;
            }

            $discount = $this->createDiscountForRule($rule, $subtotal);

            if (null === $discount) {
                continue;
            }

            if (null === $bestDiscount || $discount->getDiscountAmount() > $bestDiscount->getDiscountAmount()) {
                $bestDiscount = $discount;
            }
        }

        return $bestDiscount;
    }

    public function estimateCartSavings(WC_Cart $cart): float
    {
        $discount = $this->resolveCartDiscount($cart);

        return null !== $discount ? $discount->getDiscountAmount() : 0.0;
    }

    public function getProgressNotice(WC_Cart $cart): ?array
    {
        $subtotal     = (float) $cart->get_subtotal();
        $closestRule  = null;
        $closestDelta = null;

        foreach ($this->getActiveRules() as $rule) {
            if ('cart_subtotal_discount' !== $rule->getRuleType()) {
                continue;
            }

            if (! (bool) $this->ruleDataAccessor->getActionValue($rule, 'savings_message_enabled', false)) {
                continue;
            }

            $minimumAmount = (float) $this->ruleDataAccessor->getConditionValue($rule, 'min_cart_amount', 0);

            if ($subtotal >= $minimumAmount) {
                $discountType  = (string) $this->ruleDataAccessor->getActionValue($rule, 'discount_type', 'percentage');
                $discountValue = (float) $this->ruleDataAccessor->getActionValue($rule, 'discount_value', 0);

                return [
                    'type'    => 'success',
                    'message' => 'percentage' === $discountType
                        ? sprintf(
                            /* translators: 1: percentage amount. */
                            __('You unlocked %1$s%% off.', 'pluginora'),
                            wc_format_decimal($discountValue, 2)
                        )
                        : sprintf(
                            /* translators: 1: formatted money amount. */
                            __('You unlocked %1$s off.', 'pluginora'),
                            wc_price($discountValue)
                        ),
                ];
            }

            $delta = $minimumAmount - $subtotal;

            if (null === $closestDelta || $delta < $closestDelta) {
                $closestDelta = $delta;
                $closestRule  = $rule;
            }
        }

        if (null === $closestRule || null === $closestDelta) {
            return null;
        }

        $discountType  = (string) $this->ruleDataAccessor->getActionValue($closestRule, 'discount_type', 'percentage');
        $discountValue = (float) $this->ruleDataAccessor->getActionValue($closestRule, 'discount_value', 0);

        return [
            'type'    => 'notice',
            'message' => 'percentage' === $discountType
                ? sprintf(
                    /* translators: 1: formatted money amount, 2: percentage amount. */
                    __('Spend %1$s more and save %2$s%%.', 'pluginora'),
                    wc_price($closestDelta),
                    wc_format_decimal($discountValue, 2)
                )
                : sprintf(
                    /* translators: 1: formatted money amount to spend, 2: formatted money discount. */
                    __('Spend %1$s more and save %2$s.', 'pluginora'),
                    wc_price($closestDelta),
                    wc_price($discountValue)
                ),
        ];
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

    private function createDiscountForRule(Rule $rule, float $subtotal): ?CartDiscount
    {
        $discountType  = (string) $this->ruleDataAccessor->getActionValue($rule, 'discount_type', 'percentage');
        $discountValue = (float) $this->ruleDataAccessor->getActionValue($rule, 'discount_value', 0);
        $amount        = 'fixed' === $discountType ? $discountValue : ($subtotal * $discountValue) / 100;
        $amount        = min($subtotal, max(0.0, $amount));

        if ($amount <= 0) {
            return null;
        }

        return new CartDiscount(
            $rule,
            $subtotal,
            $amount,
            (float) $this->ruleDataAccessor->getConditionValue($rule, 'min_cart_amount', 0),
            null !== $this->ruleDataAccessor->getConditionValue($rule, 'max_cart_amount')
                ? (float) $this->ruleDataAccessor->getConditionValue($rule, 'max_cart_amount')
                : null,
            $discountType,
            $discountValue,
            (bool) $this->ruleDataAccessor->getActionValue($rule, 'savings_message_enabled', false)
        );
    }
}
