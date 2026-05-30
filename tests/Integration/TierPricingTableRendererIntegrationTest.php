<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Modules\DynamicPricing\Application\ProductPricingService;
use Pluginora\Modules\DynamicPricing\Application\RuleDataAccessor;
use Pluginora\Modules\DynamicPricing\Application\RuleMatcher;
use Pluginora\Modules\DynamicPricing\Presentation\TierPricingTableRenderer;
use WC_Product_Simple;

final class TierPricingTableRendererIntegrationTest extends IntegrationTestCase
{
    private TierPricingTableRenderer $renderer;

    public function set_up(): void
    {
        parent::set_up();

        if (! class_exists('WC_Product_Simple')) {
            self::markTestSkipped('WooCommerce product APIs are required for tier pricing renderer integration tests.');
        }

        $this->renderer = new TierPricingTableRenderer(
            new ProductPricingService(
                self::$ruleQueryRepository,
                new RuleMatcher(new RuleDataAccessor()),
                new RuleDataAccessor()
            )
        );
    }

    public function test_render_outputs_tier_pricing_table_for_matching_product(): void
    {
        self::$ruleRepository->save(self::$rulePayloadMapper->fromPayload($this->makeTieredPricingPayload()));

        global $product;
        $product = $this->createProduct('Tiered Product', '90');

        ob_start();
        $this->renderer->render();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('pluginora-tier-table', $output);
        self::assertStringContainsString('Quantity Pricing', $output);
        self::assertStringContainsString('2 to 4', $output);
        self::assertStringContainsString('10.00%', $output);
        self::assertStringContainsString('5+', $output);

        $product = null;
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
