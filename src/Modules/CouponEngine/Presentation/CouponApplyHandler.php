<?php

declare(strict_types=1);

namespace Pluginora\Modules\CouponEngine\Presentation;

use Pluginora\Core\Contracts\HookableInterface;
use WC_Cart;

final class CouponApplyHandler implements HookableInterface
{
    public function register(): void
    {
        add_action('template_redirect', [$this, 'handle']);
    }

    public function handle(): void
    {
        $redirectUrl = $this->processRequest(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST
        );

        if (null === $redirectUrl) {
            return;
        }

        wp_safe_redirect($redirectUrl);
        exit;
    }

    public function processRequest(
        string $requestMethod,
        array $post,
        ?WC_Cart $cart = null,
        ?string $referer = null
    ): ?string {
        if ('POST' !== strtoupper($requestMethod)) {
            return null;
        }

        if (empty($post['pluginora_apply_coupon']) || empty($post['pluginora_coupon_nonce'])) {
            return null;
        }

        $couponCode = wc_format_coupon_code(wp_unslash((string) $post['pluginora_apply_coupon']));
        $nonce      = sanitize_text_field(wp_unslash((string) $post['pluginora_coupon_nonce']));

        if (! wp_verify_nonce($nonce, 'pluginora_apply_coupon_' . $couponCode)) {
            return null;
        }

        $cart = $cart ?? (function_exists('WC') ? WC()->cart : null);

        if (null === $cart) {
            return null;
        }

        $cart->apply_coupon($couponCode);

        return $referer ?: wc_get_cart_url();
    }
}
