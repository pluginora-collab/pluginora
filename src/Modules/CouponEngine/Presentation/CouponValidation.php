<?php

declare(strict_types=1);

namespace Pluginora\Modules\CouponEngine\Presentation;

use Closure;
use Pluginora\Core\Application\ConflictResolver;
use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Modules\CouponEngine\Application\CouponRuleMatcher;
use Pluginora\Repository\Contracts\RuleRepositoryInterface;
use WC_Coupon;

final class CouponValidation implements HookableInterface
{
    public function __construct(
        private readonly RuleRepositoryInterface $ruleRepository,
        private readonly CouponRuleMatcher $couponRuleMatcher,
        private readonly ConflictResolver $conflictResolver,
        private readonly ?Closure $isAdminResolver = null
    ) {
    }

    public function register(): void
    {
        add_filter('woocommerce_coupon_is_valid', [$this, 'validateDateWindow'], 20, 3);
    }

    public function validateDateWindow(bool $valid, WC_Coupon $coupon, mixed $discountContext): bool
    {
        unset($discountContext);

        if (! $valid) {
            return false;
        }

        $ruleId = (int) get_post_meta($coupon->get_id(), '_pluginora_rule_id', true);

        if ($ruleId <= 0) {
            return true;
        }

        $rule = $this->ruleRepository->find($ruleId);

        if (null === $rule) {
            return true;
        }

        if (function_exists('WC') && null !== WC()->cart && ! WC()->cart->is_empty()) {
            if (! $this->conflictResolver->shouldApplyCouponRule(WC()->cart, $rule)) {
                if (! $this->isAdminContext()) {
                    wc_add_notice(
                        __('This coupon cannot be used with the current cart discounts.', 'pluginora'),
                        'error'
                    );
                }

                return false;
            }
        }

        if (! $this->couponRuleMatcher->isWithinDateWindow($rule, gmdate('Y-m-d H:i:s'))) {
            if (! $this->isAdminContext()) {
                wc_add_notice(__('This coupon is not active right now.', 'pluginora'), 'error');
            }

            return false;
        }

        return true;
    }

    private function isAdminContext(): bool
    {
        if ($this->isAdminResolver instanceof Closure) {
            return (bool) ($this->isAdminResolver)();
        }

        return is_admin();
    }
}
