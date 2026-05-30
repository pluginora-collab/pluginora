<?php

declare(strict_types=1);

namespace Pluginora\Modules\CouponEngine\Presentation;

use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Modules\CouponEngine\Application\AvailableCouponProvider;
use Pluginora\Modules\CouponEngine\Application\RuleDataAccessor;

final class AvailableCouponsRenderer implements HookableInterface
{
    public function __construct(
        private readonly AvailableCouponProvider $availableCouponProvider,
        private readonly RuleDataAccessor $ruleDataAccessor
    ) {
    }

    public function register(): void
    {
        add_action('woocommerce_before_cart', fn () => $this->render('cart'));
        add_action('woocommerce_before_checkout_form', fn () => $this->render('checkout'));
        add_action('woocommerce_before_my_account', fn () => $this->render('myaccount'));
    }

    public function render(string $location): void
    {
        $rules = $this->availableCouponProvider->getForLocation($location);

        if ([] === $rules) {
            return;
        }

        echo '<section class="pluginora-coupon-list">';
        echo '<h3>' . esc_html__('Available Coupons', 'pluginora') . '</h3>';
        echo '<div class="pluginora-coupon-grid">';

        foreach ($rules as $rule) {
            $couponCode  = (string) $this->ruleDataAccessor->getActionValue($rule, 'coupon_code', '');
            $description = (string) $this->ruleDataAccessor->getActionValue($rule, 'coupon_description', '');

            echo '<article class="pluginora-coupon-card">';
            echo '<strong class="pluginora-coupon-code">' . esc_html($couponCode) . '</strong>';

            if ('' !== $description) {
                echo '<p>' . esc_html($description) . '</p>';
            }

            echo '<form method="post" class="pluginora-coupon-form">';
            wp_nonce_field('pluginora_apply_coupon_' . $couponCode, 'pluginora_coupon_nonce');
            echo '<input type="hidden" name="pluginora_apply_coupon" value="' . esc_attr($couponCode) . '" />';
            echo '<button type="submit" class="button">' . esc_html__('Apply Coupon', 'pluginora') . '</button>';
            echo '</form>';
            echo '</article>';
        }

        echo '</div>';
        echo '</section>';
    }
}
