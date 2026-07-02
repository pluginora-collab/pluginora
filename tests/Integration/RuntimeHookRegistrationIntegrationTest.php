<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Modules\CouponEngine\Application\CouponRuleMatcher;
use Pluginora\Modules\CouponEngine\Application\RuleDataAccessor as CouponRuleDataAccessor;
use Pluginora\Modules\CouponEngine\Presentation\AutoApplyCoupons;
use Pluginora\Modules\CouponEngine\Presentation\BogoCartManager;
use Pluginora\Modules\CouponEngine\Presentation\CouponApplyHandler;
use Pluginora\Modules\CouponEngine\Presentation\CouponValidation;
use Pluginora\Modules\DynamicPricing\Application\CartDiscountService;
use Pluginora\Modules\DynamicPricing\Application\ProductPricingService;
use Pluginora\Modules\DynamicPricing\Application\RuleDataAccessor as DynamicPricingRuleDataAccessor;
use Pluginora\Modules\DynamicPricing\Application\RuleMatcher;
use Pluginora\Modules\DynamicPricing\Presentation\CartNoticeRenderer;
use Pluginora\Modules\DynamicPricing\Presentation\CartPriceAdjustments;
use Pluginora\Modules\DynamicPricing\Presentation\ProductPriceRenderer;

final class RuntimeHookRegistrationIntegrationTest extends IntegrationTestCase
{
    private ProductPriceRenderer $productPriceRenderer;

    private CartPriceAdjustments $cartPriceAdjustments;

    private CartNoticeRenderer $cartNoticeRenderer;

    private AutoApplyCoupons $autoApplyCoupons;

    private BogoCartManager $bogoCartManager;

    private CouponApplyHandler $couponApplyHandler;

    private CouponValidation $couponValidation;

    public function set_up(): void
    {
        parent::set_up();

        $dynamicPricingRuleDataAccessor = new DynamicPricingRuleDataAccessor();
        $dynamicPricingRuleMatcher = new RuleMatcher($dynamicPricingRuleDataAccessor);
        $productPricingService = new ProductPricingService(
            self::$ruleQueryRepository,
            $dynamicPricingRuleMatcher,
            $dynamicPricingRuleDataAccessor
        );
        $cartDiscountService = new CartDiscountService(
            self::$ruleQueryRepository,
            $dynamicPricingRuleMatcher,
            $dynamicPricingRuleDataAccessor
        );
        $couponRuleDataAccessor = new CouponRuleDataAccessor();
        $couponRuleMatcher = new CouponRuleMatcher($couponRuleDataAccessor);

        $this->productPriceRenderer = new ProductPriceRenderer($productPricingService);
        $this->cartPriceAdjustments = new CartPriceAdjustments(
            $productPricingService,
            $cartDiscountService,
            self::$conflictResolver
        );
        $this->cartNoticeRenderer = new CartNoticeRenderer($cartDiscountService, self::$conflictResolver);
        $this->autoApplyCoupons = new AutoApplyCoupons(
            self::$ruleQueryRepository,
            $couponRuleMatcher,
            $couponRuleDataAccessor,
            self::$conflictResolver
        );
        $this->bogoCartManager = new BogoCartManager(
            self::$ruleQueryRepository,
            $couponRuleMatcher,
            $couponRuleDataAccessor,
            self::$conflictResolver
        );
        $this->couponApplyHandler = new CouponApplyHandler();
        $this->couponValidation = new CouponValidation(
            self::$ruleRepository,
            $couponRuleMatcher,
            self::$conflictResolver
        );
    }

    public function tear_down(): void
    {
        remove_filter('woocommerce_get_price_html', [$this->productPriceRenderer, 'filterPriceHtml'], 20);
        remove_filter('woocommerce_sale_flash', [$this->productPriceRenderer, 'filterSaleFlash'], 20);
        remove_filter('woocommerce_product_is_on_sale', [$this->productPriceRenderer, 'filterIsOnSale'], 20);

        remove_action(
            'woocommerce_before_calculate_totals',
            [$this->cartPriceAdjustments, 'applyItemPrices'],
            20
        );
        remove_action('woocommerce_cart_calculate_fees', [$this->cartPriceAdjustments, 'applyCartFee'], 20);
        remove_filter('woocommerce_cart_item_price', [$this->cartPriceAdjustments, 'renderCartItemPrice'], 20);
        remove_filter('woocommerce_cart_item_subtotal', [$this->cartPriceAdjustments, 'renderCartItemSubtotal'], 20);

        remove_action('woocommerce_before_cart', [$this->cartNoticeRenderer, 'renderNotice']);
        remove_action('woocommerce_before_checkout_form', [$this->cartNoticeRenderer, 'renderNotice']);

        remove_action('woocommerce_before_cart', [$this->autoApplyCoupons, 'maybeApplyCoupons']);
        remove_action('woocommerce_before_checkout_form', [$this->autoApplyCoupons, 'maybeApplyCoupons']);
        remove_action(
            'woocommerce_before_calculate_totals',
            [$this->autoApplyCoupons, 'maybeApplyCouponsToCart']
        );
        remove_action('woocommerce_removed_coupon', [$this->autoApplyCoupons, 'onCouponRemoved']);
        remove_action('woocommerce_applied_coupon', [$this->autoApplyCoupons, 'onCouponApplied']);

        remove_action(
            'woocommerce_before_calculate_totals',
            [$this->bogoCartManager, 'syncRewards'],
            25
        );
        remove_action(
            'woocommerce_before_calculate_totals',
            [$this->bogoCartManager, 'priceRewards'],
            30
        );
        remove_filter('woocommerce_cart_item_name', [$this->bogoCartManager, 'decorateRewardItemName'], 20);

        remove_action('template_redirect', [$this->couponApplyHandler, 'handle']);
        remove_filter('woocommerce_coupon_is_valid', [$this->couponValidation, 'validateDateWindow'], 20);

        parent::tear_down();
    }

    public function test_dynamic_pricing_presenters_register_expected_hooks(): void
    {
        $this->productPriceRenderer->register();
        $this->cartPriceAdjustments->register();
        $this->cartNoticeRenderer->register();

        self::assertSame(
            20,
            has_filter('woocommerce_get_price_html', [$this->productPriceRenderer, 'filterPriceHtml'])
        );
        self::assertSame(20, has_filter('woocommerce_sale_flash', [$this->productPriceRenderer, 'filterSaleFlash']));
        self::assertSame(
            20,
            has_filter('woocommerce_product_is_on_sale', [$this->productPriceRenderer, 'filterIsOnSale'])
        );

        self::assertSame(
            20,
            has_action('woocommerce_before_calculate_totals', [$this->cartPriceAdjustments, 'applyItemPrices'])
        );
        self::assertSame(
            20,
            has_action('woocommerce_cart_calculate_fees', [$this->cartPriceAdjustments, 'applyCartFee'])
        );
        self::assertSame(
            20,
            has_filter('woocommerce_cart_item_price', [$this->cartPriceAdjustments, 'renderCartItemPrice'])
        );
        self::assertSame(
            20,
            has_filter('woocommerce_cart_item_subtotal', [$this->cartPriceAdjustments, 'renderCartItemSubtotal'])
        );

        self::assertNotFalse(
            has_action('woocommerce_before_cart', [$this->cartNoticeRenderer, 'renderNotice'])
        );
        self::assertNotFalse(
            has_action('woocommerce_before_checkout_form', [$this->cartNoticeRenderer, 'renderNotice'])
        );
    }

    public function test_coupon_engine_presenters_register_expected_hooks(): void
    {
        $this->autoApplyCoupons->register();
        $this->bogoCartManager->register();
        $this->couponApplyHandler->register();
        $this->couponValidation->register();

        self::assertNotFalse(has_action('woocommerce_before_cart', [$this->autoApplyCoupons, 'maybeApplyCoupons']));
        self::assertNotFalse(
            has_action('woocommerce_before_checkout_form', [$this->autoApplyCoupons, 'maybeApplyCoupons'])
        );
        self::assertSame(
            10,
            has_action('woocommerce_before_calculate_totals', [$this->autoApplyCoupons, 'maybeApplyCouponsToCart'])
        );
        self::assertNotFalse(has_action('woocommerce_removed_coupon', [$this->autoApplyCoupons, 'onCouponRemoved']));
        self::assertNotFalse(has_action('woocommerce_applied_coupon', [$this->autoApplyCoupons, 'onCouponApplied']));

        self::assertSame(
            25,
            has_action('woocommerce_before_calculate_totals', [$this->bogoCartManager, 'syncRewards'])
        );
        self::assertSame(
            30,
            has_action('woocommerce_before_calculate_totals', [$this->bogoCartManager, 'priceRewards'])
        );
        self::assertSame(
            20,
            has_filter('woocommerce_cart_item_name', [$this->bogoCartManager, 'decorateRewardItemName'])
        );

        self::assertNotFalse(has_action('template_redirect', [$this->couponApplyHandler, 'handle']));
        self::assertSame(
            20,
            has_filter('woocommerce_coupon_is_valid', [$this->couponValidation, 'validateDateWindow'])
        );
    }
}
