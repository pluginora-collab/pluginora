<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Presentation;

use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Modules\DynamicPricing\Application\ProductPricingService;
use Pluginora\Modules\DynamicPricing\Domain\ProductDiscount;
use WC_Product;

final class ProductPriceRenderer implements HookableInterface
{
    public function __construct(private readonly ProductPricingService $productPricingService)
    {
    }

    public function register(): void
    {
        add_filter('woocommerce_get_price_html', [$this, 'filterPriceHtml'], 20, 2);
        add_filter('woocommerce_sale_flash', [$this, 'filterSaleFlash'], 20, 3);
        add_filter('woocommerce_product_is_on_sale', [$this, 'filterIsOnSale'], 20, 2);
    }

    public function filterPriceHtml(string $priceHtml, WC_Product $product): string
    {
        $discount = $this->productPricingService->resolveProductDiscount($product, 1);

        if (null === $discount) {
            return $priceHtml;
        }

        $html = '<span class="pluginora-price">' . wc_format_sale_price(
            wc_price($discount->getOriginalPrice()),
            wc_price($discount->getDiscountedPrice())
        ) . $product->get_price_suffix() . '</span>';

        if ($discount->isSavingsMessageEnabled()) {
            $html .= $this->renderSavingsMessage($discount);
        }

        return $html;
    }

    public function filterSaleFlash(string $html, $post, WC_Product $product): string
    {
        $discount = $this->productPricingService->resolveProductDiscount($product, 1);

        if (null === $discount || ! $discount->isBadgeEnabled()) {
            return $html;
        }

        $badgeText = $discount->getBadgeText();

        if ('' === $badgeText) {
            $badgeText = $discount->getSavingsPercent() > 0
                ? sprintf('-%s%%', wc_format_decimal($discount->getSavingsPercent(), 0))
                : __('SALE', 'pluginora');
        }

        return sprintf('<span class="onsale pluginora-onsale">%s</span>', esc_html($badgeText));
    }

    public function filterIsOnSale(bool $isOnSale, WC_Product $product): bool
    {
        if ($isOnSale) {
            return true;
        }

        $discount = $this->productPricingService->resolveProductDiscount($product, 1);

        return null !== $discount && $discount->isBadgeEnabled();
    }

    private function renderSavingsMessage(ProductDiscount $discount): string
    {
        if ('percentage' === $discount->getDiscountType()) {
            return sprintf(
                '<small class="pluginora-savings-message">%s</small>',
                esc_html(
                    sprintf(
                        /* translators: 1: percentage amount. */
                        __('You save %s%%', 'pluginora'),
                        wc_format_decimal($discount->getDiscountValue(), 2)
                    )
                )
            );
        }

        return sprintf(
            '<small class="pluginora-savings-message">%s</small>',
            esc_html(
                sprintf(
                    /* translators: 1: formatted money amount. */
                    __('You saved %s', 'pluginora'),
                    wp_strip_all_tags(wc_price($discount->getSavingsAmount()))
                )
            )
        );
    }
}
