<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Presentation;

use Pluginora\Core\Application\ConflictResolver;
use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Modules\DynamicPricing\Application\CartDiscountService;
use Pluginora\Modules\DynamicPricing\Application\ProductPricingService;
use WC_Cart;

final class CartPriceAdjustments implements HookableInterface
{
    private bool $processing = false;

    public function __construct(
        private readonly ProductPricingService $productPricingService,
        private readonly CartDiscountService $cartDiscountService,
        private readonly ConflictResolver $conflictResolver
    ) {
    }

    public function register(): void
    {
        add_action('woocommerce_before_calculate_totals', [$this, 'applyItemPrices'], 20);
        add_action('woocommerce_cart_calculate_fees', [$this, 'applyCartFee'], 20);
        add_filter('woocommerce_cart_item_price', [$this, 'renderCartItemPrice'], 20, 3);
        add_filter('woocommerce_cart_item_subtotal', [$this, 'renderCartItemSubtotal'], 20, 3);
    }

    public function applyItemPrices(WC_Cart $cart): void
    {
        if ($this->processing || (is_admin() && ! wp_doing_ajax())) {
            return;
        }

        if (! $this->conflictResolver->shouldApplyDynamicPricing($cart)) {
            $this->restoreOriginalPrices($cart);
            return;
        }

        $this->processing = true;

        try {
            foreach ($cart->get_cart() as $cartItemKey => $cartItem) {
                if (empty($cartItem['data']) || ! is_object($cartItem['data'])) {
                    continue;
                }

                $product       = $cartItem['data'];
                $originalPrice = isset($cartItem['pluginora_original_price'])
                    ? (float) $cartItem['pluginora_original_price']
                    : (float) $product->get_price('edit');
                $discount      = $this->productPricingService->resolveProductDiscount(
                    $product,
                    (int) $cartItem['quantity'],
                    $originalPrice
                );

                if (null === $discount) {
                    $product->set_price($originalPrice);
                    unset($cart->cart_contents[$cartItemKey]['pluginora_pricing']);
                    $cart->cart_contents[$cartItemKey]['pluginora_original_price'] = $originalPrice;
                    continue;
                }

                $product->set_price($discount->getDiscountedPrice());
                $cart->cart_contents[$cartItemKey]['pluginora_original_price'] = $originalPrice;
                $cart->cart_contents[$cartItemKey]['pluginora_pricing'] = [
                    'rule_id'                 => $discount->getRule()->getId(),
                    'rule_name'               => $discount->getRule()->getName(),
                    'original_price'          => $discount->getOriginalPrice(),
                    'discounted_price'        => $discount->getDiscountedPrice(),
                    'discount_type'           => $discount->getDiscountType(),
                    'discount_value'          => $discount->getDiscountValue(),
                    'savings_message_enabled' => $discount->isSavingsMessageEnabled(),
                ];
            }
        } finally {
            $this->processing = false;
        }
    }

    public function applyCartFee(WC_Cart $cart): void
    {
        if (is_admin() && ! wp_doing_ajax()) {
            return;
        }

        if (! $this->conflictResolver->shouldApplyDynamicPricing($cart)) {
            return;
        }

        $discount = $this->cartDiscountService->resolveCartDiscount($cart);

        if (null === $discount || $discount->getDiscountAmount() <= 0) {
            return;
        }

        $cart->add_fee($discount->getRule()->getName(), -1 * $discount->getDiscountAmount(), false);
    }

    public function renderCartItemPrice(string $priceHtml, array $cartItem, string $cartItemKey): string
    {
        unset($cartItemKey);

        return $this->renderDiscountedCartHtml($priceHtml, $cartItem, 1);
    }

    public function renderCartItemSubtotal(string $subtotalHtml, array $cartItem, string $cartItemKey): string
    {
        unset($cartItemKey);

        return $this->renderDiscountedCartHtml($subtotalHtml, $cartItem, (int) $cartItem['quantity']);
    }

    private function renderDiscountedCartHtml(string $html, array $cartItem, int $quantityMultiplier): string
    {
        if (empty($cartItem['pluginora_pricing'])) {
            return $html;
        }

        $pricing        = $cartItem['pluginora_pricing'];
        $originalAmount = (float) $pricing['original_price'] * $quantityMultiplier;
        $currentAmount  = (float) $pricing['discounted_price'] * $quantityMultiplier;
        $renderedHtml   = sprintf(
            '<span class="pluginora-cart-price"><del>%1$s</del> <ins>%2$s</ins></span>',
            wp_kses_post(wc_price($originalAmount)),
            wp_kses_post(wc_price($currentAmount))
        );

        if (! empty($pricing['savings_message_enabled'])) {
            $savedAmount = max(0.0, $originalAmount - $currentAmount);
            $renderedHtml .= sprintf(
                '<small class="pluginora-savings-message">%s</small>',
                esc_html(
                    sprintf(
                        /* translators: 1: formatted money amount. */
                        __('You saved %s', 'pluginora'),
                        wp_strip_all_tags(wc_price($savedAmount))
                    )
                )
            );
        }

        return $renderedHtml;
    }

    private function restoreOriginalPrices(WC_Cart $cart): void
    {
        foreach ($cart->get_cart() as $cartItemKey => $cartItem) {
            if (empty($cartItem['data']) || ! is_object($cartItem['data'])) {
                continue;
            }

            $originalPrice = isset($cartItem['pluginora_original_price'])
                ? (float) $cartItem['pluginora_original_price']
                : (float) $cartItem['data']->get_price('edit');

            $cartItem['data']->set_price($originalPrice);
            unset($cart->cart_contents[$cartItemKey]['pluginora_pricing']);
            $cart->cart_contents[$cartItemKey]['pluginora_original_price'] = $originalPrice;
        }
    }
}
