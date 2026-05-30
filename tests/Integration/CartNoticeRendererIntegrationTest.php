<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Core\Settings\SettingsRepository;
use Pluginora\Modules\DynamicPricing\Application\CartDiscountService;
use Pluginora\Modules\DynamicPricing\Application\RuleDataAccessor;
use Pluginora\Modules\DynamicPricing\Application\RuleMatcher;
use Pluginora\Modules\DynamicPricing\Presentation\CartNoticeRenderer;
use WC_Cart;

final class CartNoticeRendererIntegrationTest extends IntegrationTestCase
{
    private CartNoticeRenderer $renderer;

    public function set_up(): void
    {
        parent::set_up();

        if (! class_exists('WC_Cart')) {
            self::markTestSkipped('WooCommerce cart APIs are required for cart notice integration tests.');
        }

        $this->renderer = new CartNoticeRenderer(
            new CartDiscountService(
                self::$ruleQueryRepository,
                new RuleMatcher(new RuleDataAccessor()),
                new RuleDataAccessor()
            ),
            self::$conflictResolver
        );
    }

    public function test_get_notice_for_cart_returns_progress_message_before_threshold(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'stack_all']);

        self::$ruleRepository->save(self::$rulePayloadMapper->fromPayload($this->makeCartSubtotalDiscountPayload()));

        $cart = $this->createCartStub(70.0);
        $notice = $this->renderer->getNoticeForCart($cart);

        self::assertSame('notice', $notice['type']);
        self::assertStringContainsString('Spend', wp_strip_all_tags($notice['message']));
        self::assertStringContainsString('save', wp_strip_all_tags($notice['message']));
    }

    public function test_get_notice_for_cart_returns_success_message_after_threshold(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'stack_all']);

        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeCartSubtotalDiscountPayload(
                    [
                        'discount_type'  => 'percentage',
                        'discount_value' => 12,
                    ]
                )
            )
        );

        $cart = $this->createCartStub(150.0);
        $notice = $this->renderer->getNoticeForCart($cart);

        self::assertSame('success', $notice['type']);
        self::assertStringContainsString('You unlocked 12.00% off.', wp_strip_all_tags($notice['message']));
    }

    private function createCartStub(float $subtotal): WC_Cart
    {
        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_subtotal', 'get_cart', 'get_applied_coupons'])
            ->getMock();

        $cart->method('get_subtotal')->willReturn($subtotal);
        $cart->method('get_cart')->willReturn([]);
        $cart->method('get_applied_coupons')->willReturn([]);

        return $cart;
    }
}
