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

final class AutoApplyCoupons implements HookableInterface
{
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
        add_action('woocommerce_before_cart', [$this, 'maybeApplyCoupons']);
        add_action('woocommerce_before_checkout_form', [$this, 'maybeApplyCoupons']);
        add_action('woocommerce_before_calculate_totals', [$this, 'maybeApplyCouponsToCart']);
    }

    public function maybeApplyCoupons(): void
    {
        if (! function_exists('WC') || null === WC()->cart) {
            return;
        }

        $this->applyCoupons(WC()->cart);
    }

    public function maybeApplyCouponsToCart(WC_Cart $cart): void
    {
        $this->applyCoupons($cart);
    }

    private function applyCoupons(WC_Cart $cart): void
    {
        foreach ($this->getActiveRules() as $rule) {
            if ('auto_apply_coupon' !== $rule->getRuleType()) {
                continue;
            }

            $couponCode = sanitize_text_field(
                (string) $this->ruleDataAccessor->getActionValue($rule, 'coupon_code', '')
            );

            if ('' === $couponCode) {
                continue;
            }

            $matches = $this->couponRuleMatcher->matchesAutoApplyRule($rule, $cart);
            $applied = $cart->has_discount($couponCode);
            $allowed = $matches && $this->conflictResolver->shouldApplyCouponRule($cart, $rule);

            if ($allowed && ! $applied) {
                $result = $cart->apply_coupon($couponCode);

                if ($result) {
                    wc_add_notice(
                        sprintf(
                            /* translators: 1: coupon code. */
                            __('Coupon %s applied automatically.', 'pluginora'),
                            $couponCode
                        ),
                        'success'
                    );
                }
            }

            if ((! $matches || ! $allowed) && $applied) {
                $cart->remove_coupon($couponCode);
            }
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
