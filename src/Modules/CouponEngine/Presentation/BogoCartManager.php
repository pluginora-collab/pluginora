<?php

declare(strict_types=1);

namespace Pluginora\Modules\CouponEngine\Presentation;

use Pluginora\Core\Application\ConflictResolver;
use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Modules\CouponEngine\Application\CouponRuleMatcher;
use Pluginora\Modules\CouponEngine\Application\RuleDataAccessor;
use Pluginora\Repository\Contracts\RuleQueryRepositoryInterface;
use Pluginora\Support\Rule;
use WC_Cart;

final class BogoCartManager implements HookableInterface
{
    private bool $processing = false;

    /**
     * @var Rule[]|null
     */
    private ?array $cachedRules = null;

    public function __construct(
        private readonly RuleQueryRepositoryInterface $ruleQueryRepository,
        private readonly CouponRuleMatcher $couponRuleMatcher,
        private readonly RuleDataAccessor $ruleDataAccessor,
        private readonly ConflictResolver $conflictResolver
    ) {
    }

    public function register(): void
    {
        add_action('woocommerce_before_calculate_totals', [$this, 'syncRewards'], 25);
        add_action('woocommerce_before_calculate_totals', [$this, 'priceRewards'], 30);
        add_filter('woocommerce_cart_item_name', [$this, 'decorateRewardItemName'], 20, 3);
    }

    public function syncRewards(WC_Cart $cart): void
    {
        if ($this->processing || (is_admin() && ! wp_doing_ajax())) {
            return;
        }

        $this->processing = true;

        try {
            foreach ($this->getActiveRules() as $rule) {
                if ('bogo_coupon' !== $rule->getRuleType()) {
                    continue;
                }

                $this->syncRuleRewards($rule, $cart);
            }
        } finally {
            $this->processing = false;
        }
    }

    public function priceRewards(WC_Cart $cart): void
    {
        foreach ($cart->get_cart() as $cartItemKey => $cartItem) {
            if (
                empty($cartItem['pluginora_bogo_rule_id'])
                || empty($cartItem['data'])
                || ! is_object($cartItem['data'])
            ) {
                continue;
            }

            $product        = $cartItem['data'];
            $originalPrice  = isset($cartItem['pluginora_bogo_original_price'])
                ? (float) $cartItem['pluginora_bogo_original_price']
                : (float) $product->get_price('edit');
            $rewardType     = (string) ($cartItem['pluginora_bogo_reward_type'] ?? 'free');
            $discountValue  = (float) ($cartItem['pluginora_bogo_discount_value'] ?? 0);
            $cart->cart_contents[$cartItemKey]['pluginora_bogo_original_price'] = $originalPrice;

            if ('free' === $rewardType) {
                $product->set_price(0);
                continue;
            }

            $discountedPrice = max(0.0, $originalPrice - (($originalPrice * $discountValue) / 100));
            $product->set_price($discountedPrice);
        }
    }

    public function decorateRewardItemName(string $name, array $cartItem, string $cartItemKey): string
    {
        unset($cartItemKey);

        if (empty($cartItem['pluginora_bogo_rule_name'])) {
            return $name;
        }

        return $name . sprintf(
            '<small class="pluginora-coupon-meta">%s</small>',
            esc_html(
                sprintf(
                    /* translators: 1: promotion rule name. */
                    __('Added by %s', 'pluginora'),
                    $cartItem['pluginora_bogo_rule_name']
                )
            )
        );
    }

    private function syncRuleRewards(Rule $rule, WC_Cart $cart): void
    {
        $rewardProductIds = $this->ruleDataAccessor->getItemIds($rule, 'get_product');

        if ([] === $rewardProductIds) {
            return;
        }

        if (! $this->conflictResolver->shouldApplyCouponRule($cart, $rule)) {
            $this->removeRewardItems($cart, $this->findRewardCartItems($cart, (int) $rule->getId()));
            return;
        }

        $rewardProductId = (int) $rewardProductIds[0];
        $targetQuantity  = $this->couponRuleMatcher->getApplicableRewardQuantity($rule, $cart);
        $existingItems   = $this->findRewardCartItems($cart, (int) $rule->getId());
        $existingQty     = 0;

        foreach ($existingItems as $existingItem) {
            $existingQty += (int) $existingItem['quantity'];
        }

        if ($targetQuantity <= 0) {
            $this->removeRewardItems($cart, $existingItems);
            return;
        }

        if ([] === $existingItems) {
            $cart->add_to_cart(
                $rewardProductId,
                $targetQuantity,
                0,
                [],
                [
                    'pluginora_bogo_rule_id'        => (int) $rule->getId(),
                    'pluginora_bogo_rule_name'      => $rule->getName(),
                    'pluginora_bogo_reward_type'    => (string) $this->ruleDataAccessor->getActionValue(
                        $rule,
                        'reward_type',
                        'free'
                    ),
                    'pluginora_bogo_discount_value' => (float) $this->ruleDataAccessor->getActionValue(
                        $rule,
                        'discount_value',
                        0
                    ),
                ]
            );

            return;
        }

        if ($existingQty !== $targetQuantity) {
            $firstKey = array_key_first($existingItems);

            if (null !== $firstKey) {
                $cart->set_quantity($firstKey, $targetQuantity, false);
            }

            $extraKeys = array_slice(array_keys($existingItems), 1);

            foreach ($extraKeys as $extraKey) {
                $cart->remove_cart_item($extraKey);
            }
        }
    }

    private function findRewardCartItems(WC_Cart $cart, int $ruleId): array
    {
        $items = [];

        foreach ($cart->get_cart() as $cartItemKey => $cartItem) {
            if ((int) ($cartItem['pluginora_bogo_rule_id'] ?? 0) !== $ruleId) {
                continue;
            }

            $items[$cartItemKey] = $cartItem;
        }

        return $items;
    }

    private function removeRewardItems(WC_Cart $cart, array $rewardItems): void
    {
        foreach (array_keys($rewardItems) as $cartItemKey) {
            $cart->remove_cart_item($cartItemKey);
        }
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
}
