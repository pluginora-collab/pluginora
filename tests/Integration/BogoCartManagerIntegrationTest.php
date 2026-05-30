<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Core\Settings\SettingsRepository;
use Pluginora\Modules\CouponEngine\Application\CouponRuleMatcher;
use Pluginora\Modules\CouponEngine\Application\RuleDataAccessor;
use Pluginora\Modules\CouponEngine\Presentation\BogoCartManager;
use WC_Cart;
use WC_Product;
use WC_Product_Simple;

final class BogoCartManagerIntegrationTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        if (! class_exists('WC_Cart') || ! class_exists('WC_Product') || ! class_exists('WC_Product_Simple')) {
            self::markTestSkipped('WooCommerce cart APIs are required for BOGO integration tests.');
        }
    }

    public function test_sync_rewards_adds_reward_item_for_qualified_cart(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'stack_all']);

        $ruleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBogoCouponPayload(
                    [
                        'name'         => 'Free Mug',
                        'buy_quantity' => 2,
                    ]
                )
            )
        );

        $handler = new BogoCartManager(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $product = $this->createMock(WC_Product::class);
        $product->method('get_id')->willReturn(101);
        $product->method('get_parent_id')->willReturn(0);

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_cart', 'add_to_cart', 'set_quantity', 'remove_cart_item'])
            ->getMock();

        $cart->method('get_cart')->willReturn(
            [
                'buy-key' => [
                    'data'     => $product,
                    'quantity' => 2,
                ],
            ]
        );
        $cart->expects(self::once())
            ->method('add_to_cart')
            ->with(
                202,
                1,
                0,
                [],
                self::callback(
                    static function (array $cartItemData) use ($ruleId): bool {
                        return (int) $cartItemData['pluginora_bogo_rule_id'] === $ruleId
                            && 'Free Mug' === $cartItemData['pluginora_bogo_rule_name']
                            && 'free' === $cartItemData['pluginora_bogo_reward_type']
                            && 0.0 === (float) $cartItemData['pluginora_bogo_discount_value'];
                    }
                )
            );
        $cart->expects(self::never())->method('set_quantity');
        $cart->expects(self::never())->method('remove_cart_item');

        $handler->syncRewards($cart);
    }

    public function test_price_rewards_applies_percentage_discount_to_reward_items(): void
    {
        $handler = new BogoCartManager(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $product = $this->createMock(WC_Product::class);
        $product->expects(self::once())
            ->method('get_price')
            ->with('edit')
            ->willReturn('40');
        $product->expects(self::once())
            ->method('set_price')
            ->with(30.0);

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_cart'])
            ->getMock();

        $cart->cart_contents = [
            'reward-key' => [
                'data'                         => $product,
                'quantity'                     => 1,
                'pluginora_bogo_rule_id'       => 77,
                'pluginora_bogo_reward_type'   => 'percentage',
                'pluginora_bogo_discount_value' => 25,
            ],
        ];

        $cart->method('get_cart')->willReturn($cart->cart_contents);

        $handler->priceRewards($cart);

        self::assertSame(40.0, $cart->cart_contents['reward-key']['pluginora_bogo_original_price']);
    }

    public function test_sync_rewards_updates_existing_reward_quantity_and_removes_extra_reward_rows(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'stack_all']);

        $ruleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBogoCouponPayload(
                    [
                        'name'         => 'Bonus Item',
                        'buy_quantity' => 2,
                    ]
                )
            )
        );

        $handler = new BogoCartManager(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $buyProduct = $this->createMock(WC_Product::class);
        $buyProduct->method('get_id')->willReturn(101);
        $buyProduct->method('get_parent_id')->willReturn(0);

        $rewardProduct = $this->createMock(WC_Product::class);
        $rewardProduct->method('get_id')->willReturn(202);
        $rewardProduct->method('get_parent_id')->willReturn(0);

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_cart', 'add_to_cart', 'set_quantity', 'remove_cart_item'])
            ->getMock();

        $cart->method('get_cart')->willReturn(
            [
                'buy-key' => [
                    'data'     => $buyProduct,
                    'quantity' => 4,
                ],
                'reward-a' => [
                    'data'                   => $rewardProduct,
                    'quantity'               => 1,
                    'pluginora_bogo_rule_id' => $ruleId,
                ],
                'reward-b' => [
                    'data'                   => $rewardProduct,
                    'quantity'               => 2,
                    'pluginora_bogo_rule_id' => $ruleId,
                ],
            ]
        );
        $cart->expects(self::never())->method('add_to_cart');
        $cart->expects(self::once())
            ->method('set_quantity')
            ->with('reward-a', 2, false);
        $cart->expects(self::once())
            ->method('remove_cart_item')
            ->with('reward-b');

        $handler->syncRewards($cart);
    }

    public function test_sync_rewards_removes_existing_rewards_when_cart_no_longer_qualifies(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'stack_all']);

        $ruleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBogoCouponPayload(
                    [
                        'buy_quantity' => 3,
                    ]
                )
            )
        );

        $handler = new BogoCartManager(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $buyProduct = $this->createMock(WC_Product::class);
        $buyProduct->method('get_id')->willReturn(101);
        $buyProduct->method('get_parent_id')->willReturn(0);

        $rewardProduct = $this->createMock(WC_Product::class);
        $rewardProduct->method('get_id')->willReturn(202);
        $rewardProduct->method('get_parent_id')->willReturn(0);

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_cart', 'add_to_cart', 'set_quantity', 'remove_cart_item'])
            ->getMock();

        $cart->method('get_cart')->willReturn(
            [
                'buy-key' => [
                    'data'     => $buyProduct,
                    'quantity' => 2,
                ],
                'reward-key' => [
                    'data'                   => $rewardProduct,
                    'quantity'               => 1,
                    'pluginora_bogo_rule_id' => $ruleId,
                ],
            ]
        );
        $cart->expects(self::never())->method('add_to_cart');
        $cart->expects(self::never())->method('set_quantity');
        $cart->expects(self::once())
            ->method('remove_cart_item')
            ->with('reward-key');

        $handler->syncRewards($cart);
    }

    public function test_sync_rewards_removes_existing_rewards_when_dynamic_pricing_wins_conflict_resolution(): void
    {
        update_option(SettingsRepository::OPTION_KEY, ['conflict_mode' => 'best_discount_only']);

        $rewardProduct = $this->createSavedRewardProduct('Reward Product', '10');

        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'status'            => 'active',
                        'applies_to'        => 'selected_products',
                        'selected_products' => [101],
                        'discount_value'    => 40,
                    ]
                )
            )
        );

        $ruleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeBogoCouponPayload(
                    [
                        'buy_quantity'   => 2,
                        'get_product_id' => $rewardProduct->get_id(),
                    ]
                )
            )
        );

        $handler = new BogoCartManager(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $buyProduct = $this->createMock(WC_Product::class);
        $buyProduct->method('get_id')->willReturn(101);
        $buyProduct->method('get_parent_id')->willReturn(0);
        $buyProduct->method('get_price')->willReturnCallback(static fn (): string => '100');

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_cart', 'get_subtotal', 'add_to_cart', 'set_quantity', 'remove_cart_item'])
            ->getMock();

        $cart->method('get_subtotal')->willReturn(200.0);
        $cart->method('get_cart')->willReturn(
            [
                'buy-key' => [
                    'data'     => $buyProduct,
                    'quantity' => 2,
                ],
                'reward-key' => [
                    'data'                   => $rewardProduct,
                    'quantity'               => 1,
                    'pluginora_bogo_rule_id' => $ruleId,
                ],
            ]
        );
        $cart->expects(self::never())->method('add_to_cart');
        $cart->expects(self::never())->method('set_quantity');
        $cart->expects(self::once())
            ->method('remove_cart_item')
            ->with('reward-key');

        $handler->syncRewards($cart);
    }

    public function test_price_rewards_sets_free_reward_price_to_zero(): void
    {
        $handler = new BogoCartManager(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $product = $this->createMock(WC_Product::class);
        $product->expects(self::once())
            ->method('get_price')
            ->with('edit')
            ->willReturn('25');
        $product->expects(self::once())
            ->method('set_price')
            ->with(0);

        $cart = $this->getMockBuilder(WC_Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_cart'])
            ->getMock();

        $cart->cart_contents = [
            'reward-key' => [
                'data'                   => $product,
                'quantity'               => 1,
                'pluginora_bogo_rule_id' => 77,
                'pluginora_bogo_reward_type' => 'free',
            ],
        ];

        $cart->method('get_cart')->willReturn($cart->cart_contents);

        $handler->priceRewards($cart);

        self::assertSame(25.0, $cart->cart_contents['reward-key']['pluginora_bogo_original_price']);
    }

    public function test_decorate_reward_item_name_appends_rule_name_metadata(): void
    {
        $handler = new BogoCartManager(
            self::$ruleQueryRepository,
            new CouponRuleMatcher(new RuleDataAccessor()),
            new RuleDataAccessor(),
            self::$conflictResolver
        );

        $html = $handler->decorateRewardItemName(
            'Free Mug',
            [
                'pluginora_bogo_rule_name' => 'Summer BOGO',
            ],
            'reward-key'
        );

        self::assertStringContainsString('Free Mug', $html);
        self::assertStringContainsString('Added by Summer BOGO', wp_strip_all_tags($html));
    }

    private function createSavedRewardProduct(string $name, string $price): WC_Product_Simple
    {
        $product = new WC_Product_Simple();
        $product->set_name($name);
        $product->set_status('publish');
        $product->set_regular_price($price);
        $product->save();

        return $product;
    }
}
