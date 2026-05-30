<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Presentation;

use Pluginora\Core\Application\ConflictResolver;
use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Modules\DynamicPricing\Application\CartDiscountService;

final class CartNoticeRenderer implements HookableInterface
{
    public function __construct(
        private readonly CartDiscountService $cartDiscountService,
        private readonly ConflictResolver $conflictResolver
    ) {
    }

    public function register(): void
    {
        add_action('woocommerce_before_cart', [$this, 'renderNotice']);
        add_action('woocommerce_before_checkout_form', [$this, 'renderNotice']);
    }

    public function renderNotice(): void
    {
        if (! function_exists('WC') || null === WC()->cart) {
            return;
        }

        $notice = $this->getNoticeForCart(WC()->cart);

        if (null === $notice) {
            return;
        }

        wc_print_notice($notice['message'], $notice['type']);
    }

    public function getNoticeForCart($cart): ?array
    {
        if (null === $cart) {
            return null;
        }

        if (! $this->conflictResolver->shouldApplyDynamicPricing($cart)) {
            return null;
        }

        return $this->cartDiscountService->getProgressNotice($cart);
    }
}
