<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Modules\DynamicPricing\Application\ProductPricingService;
use Pluginora\Modules\DynamicPricing\Application\RuleDataAccessor;
use Pluginora\Modules\DynamicPricing\Application\RuleMatcher;
use Pluginora\Modules\DynamicPricing\Presentation\ProductPriceRenderer;
use WC_Product_Simple;

final class ProductPriceRendererIntegrationTest extends IntegrationTestCase
{
    private ProductPriceRenderer $renderer;

    public function set_up(): void
    {
        parent::set_up();

        if (! class_exists('WC_Product_Simple')) {
            self::markTestSkipped(
                'WooCommerce product APIs are required for product price renderer integration tests.'
            );
        }

        $this->renderer = new ProductPriceRenderer(
            new ProductPricingService(
                self::$ruleQueryRepository,
                new RuleMatcher(new RuleDataAccessor()),
                new RuleDataAccessor()
            )
        );
    }

    public function test_filter_price_html_renders_sale_price_and_savings_message(): void
    {
        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'status'                  => 'active',
                        'applies_to'              => 'all_products',
                        'selected_products'       => [],
                        'discount_value'          => 15,
                        'badge_enabled'           => true,
                        'badge_text'              => '-15%',
                        'savings_message_enabled' => true,
                    ]
                )
            )
        );

        $product = $this->createProduct('Discounted Product', '100');

        $html = $this->renderer->filterPriceHtml('<span class="price">$100</span>', $product);

        self::assertStringContainsString('pluginora-price', $html);
        self::assertStringContainsString('pluginora-savings-message', $html);
        self::assertStringContainsString('You save 15.00%', wp_strip_all_tags($html));
    }

    public function test_filter_sale_flash_uses_rule_badge_text(): void
    {
        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'status'            => 'active',
                        'applies_to'        => 'all_products',
                        'selected_products' => [],
                        'discount_value'    => 10,
                        'badge_enabled'     => true,
                        'badge_text'        => 'Hot Deal',
                    ]
                )
            )
        );

        $product = $this->createProduct('Badge Product', '80');

        $html = $this->renderer->filterSaleFlash('<span class="onsale">Sale</span>', null, $product);

        self::assertSame('<span class="onsale pluginora-onsale">Hot Deal</span>', $html);
    }

    public function test_filter_is_on_sale_returns_true_for_products_with_pluginora_badges(): void
    {
        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'status'            => 'active',
                        'applies_to'        => 'all_products',
                        'selected_products' => [],
                        'discount_value'    => 10,
                        'badge_enabled'     => true,
                        'badge_text'        => 'Hot Deal',
                    ]
                )
            )
        );

        $product = $this->createProduct('Badge Visibility Product', '80');

        self::assertTrue($this->renderer->filterIsOnSale(false, $product));
    }

    private function createProduct(string $name, string $price): WC_Product_Simple
    {
        $product = new WC_Product_Simple();
        $product->set_name($name);
        $product->set_status('publish');
        $product->set_regular_price($price);
        $product->save();

        return $product;
    }
}
