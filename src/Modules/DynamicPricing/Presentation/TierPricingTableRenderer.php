<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Presentation;

use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Modules\DynamicPricing\Application\ProductPricingService;
use WC_Product;

final class TierPricingTableRenderer implements HookableInterface
{
    public function __construct(private readonly ProductPricingService $productPricingService)
    {
    }

    public function register(): void
    {
        add_action('woocommerce_after_add_to_cart_button', [$this, 'render']);
    }

    public function render(): void
    {
        global $product;

        if (! $product instanceof WC_Product) {
            return;
        }

        $rule = $this->productPricingService->getTieredPricingRule($product);

        if (null === $rule || [] === $rule->getTiers()) {
            return;
        }

        echo '<div class="pluginora-tier-table">';
        echo '<strong>' . esc_html__('Quantity Pricing', 'pluginora') . '</strong>';
        echo '<table class="shop_table shop_table_responsive pluginora-tier-pricing-table"><thead><tr>';
        echo '<th>' . esc_html__('Qty', 'pluginora') . '</th>';
        echo '<th>' . esc_html__('Discount', 'pluginora') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rule->getTiers() as $tier) {
            $range = null === $tier->getMaxQuantity()
                ? sprintf(
                    /* translators: 1: minimum quantity threshold. */
                    __('%d+', 'pluginora'),
                    $tier->getMinQuantity()
                )
                : sprintf(
                    /* translators: 1: minimum quantity, 2: maximum quantity. */
                    __('%1$d to %2$d', 'pluginora'),
                    $tier->getMinQuantity(),
                    $tier->getMaxQuantity()
                );
            $discount = 'fixed' === $tier->getDiscountType()
                ? wc_price($tier->getDiscountValue())
                : sprintf('%s%%', wc_format_decimal($tier->getDiscountValue(), 2));

            echo '<tr>';
            echo '<td>' . esc_html($range) . '</td>';
            echo '<td>' . wp_kses_post($discount) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }
}
