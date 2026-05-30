<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Admin\Api\RulesController;
use Pluginora\Admin\Forms\RulePayloadMapper;
use Pluginora\Admin\Forms\RuleSchemaProvider;
use Pluginora\Core\Application\ConflictResolver;
use Pluginora\Core\Database\RuleTables;
use Pluginora\Core\Database\SchemaInstaller;
use Pluginora\Core\Settings\SettingsRepository;
use Pluginora\Modules\CouponEngine\Application\CouponRuleMatcher;
use Pluginora\Modules\CouponEngine\Application\CouponSavingsEstimator;
use Pluginora\Modules\CouponEngine\Application\NativeCouponSyncService;
use Pluginora\Modules\CouponEngine\Application\RuleDataAccessor as CouponRuleDataAccessor;
use Pluginora\Modules\DynamicPricing\Application\CartDiscountService;
use Pluginora\Modules\DynamicPricing\Application\ProductPricingService;
use Pluginora\Modules\DynamicPricing\Application\RuleDataAccessor as DynamicPricingRuleDataAccessor;
use Pluginora\Modules\DynamicPricing\Application\RuleMatcher;
use Pluginora\Repository\Wpdb\WpdbRuleQueryRepository;
use Pluginora\Repository\Wpdb\WpdbRuleRepository;
use WP_UnitTestCase;

abstract class IntegrationTestCase extends WP_UnitTestCase
{
    protected static ?RuleTables $tables = null;

    protected static ?WpdbRuleRepository $ruleRepository = null;

    protected static ?WpdbRuleQueryRepository $ruleQueryRepository = null;

    protected static ?RuleSchemaProvider $ruleSchemaProvider = null;

    protected static ?RulePayloadMapper $rulePayloadMapper = null;

    protected static ?RulesController $rulesController = null;

    protected static ?SettingsRepository $settingsRepository = null;

    protected static ?NativeCouponSyncService $nativeCouponSyncService = null;

    protected static ?ConflictResolver $conflictResolver = null;

    protected static int $adminUserId = 0;

    public static function set_up_before_class(): void
    {
        parent::set_up_before_class();

        global $wpdb;

        self::$tables              = new RuleTables($wpdb->prefix);
        self::$ruleRepository      = new WpdbRuleRepository($wpdb, self::$tables);
        self::$ruleQueryRepository = new WpdbRuleQueryRepository($wpdb, self::$tables, self::$ruleRepository);
        self::$ruleSchemaProvider  = new RuleSchemaProvider();
        self::$rulePayloadMapper   = new RulePayloadMapper(self::$ruleSchemaProvider);
        self::$settingsRepository  = new SettingsRepository();
        self::$nativeCouponSyncService = new NativeCouponSyncService(new CouponRuleDataAccessor());
        self::$rulesController     = new RulesController(
            self::$ruleRepository,
            self::$ruleQueryRepository,
            self::$ruleSchemaProvider,
            self::$rulePayloadMapper
        );
        self::$conflictResolver    = new ConflictResolver(
            self::$settingsRepository,
            new ProductPricingService(
                self::$ruleQueryRepository,
                new RuleMatcher(new DynamicPricingRuleDataAccessor()),
                new DynamicPricingRuleDataAccessor()
            ),
            new CartDiscountService(
                self::$ruleQueryRepository,
                new RuleMatcher(new DynamicPricingRuleDataAccessor()),
                new DynamicPricingRuleDataAccessor()
            ),
            new CouponSavingsEstimator(
                self::$ruleQueryRepository,
                new CouponRuleDataAccessor(),
                new CouponRuleMatcher(new CouponRuleDataAccessor()),
                self::$nativeCouponSyncService
            )
        );

        (new SchemaInstaller($wpdb, self::$tables))->install();

        self::$adminUserId = self::factory()->user->create(
            [
                'role' => 'administrator',
            ]
        );

        $user = get_user_by('id', self::$adminUserId);

        if ($user) {
            $user->add_cap('manage_woocommerce');
        }
    }

    public function set_up(): void
    {
        parent::set_up();

        wp_set_current_user(self::$adminUserId);

        global $wpdb;

        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
        $wpdb->query('TRUNCATE TABLE ' . self::$tables->conditions());
        $wpdb->query('TRUNCATE TABLE ' . self::$tables->actions());
        $wpdb->query('TRUNCATE TABLE ' . self::$tables->items());
        $wpdb->query('TRUNCATE TABLE ' . self::$tables->tiers());
        $wpdb->query('TRUNCATE TABLE ' . self::$tables->logs());
        $wpdb->query('TRUNCATE TABLE ' . self::$tables->rules());
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');

        delete_option(SettingsRepository::OPTION_KEY);
        add_option(SettingsRepository::OPTION_KEY, SettingsRepository::defaults());
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeSimpleDiscountPayload(array $overrides = []): array
    {
        return array_replace_recursive(
            [
                'module'                  => 'dynamic_pricing',
                'rule_type'               => 'simple_discount',
                'name'                    => 'Integration Discount',
                'status'                  => 'inactive',
                'priority'                => 10,
                'discount_type'           => 'percentage',
                'discount_value'          => 15,
                'applies_to'              => 'selected_products',
                'selected_products'       => [101, 202],
                'selected_categories'     => [],
                'excluded_products'       => [303],
                'badge_enabled'           => true,
                'badge_text'              => '-15%',
                'savings_message_enabled' => true,
            ],
            $overrides
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeAutoApplyCouponPayload(array $overrides = []): array
    {
        return array_replace_recursive(
            [
                'module'                => 'coupon_engine',
                'rule_type'             => 'auto_apply_coupon',
                'name'                  => 'Integration Auto Coupon',
                'status'                => 'active',
                'priority'              => 5,
                'coupon_code'           => 'AUTO30',
                'coupon_description'    => 'Integration coupon',
                'coupon_discount_type'  => 'percent',
                'coupon_amount'         => 30,
                'usage_limit'           => 50,
                'free_shipping'         => false,
                'display_locations'     => ['cart'],
                'min_cart_amount'       => 50,
                'selected_products'     => [101],
                'selected_categories'   => [],
                'excluded_products'     => [],
            ],
            $overrides
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeBasicCouponPayload(array $overrides = []): array
    {
        return array_replace_recursive(
            [
                'module'                => 'coupon_engine',
                'rule_type'             => 'basic_coupon',
                'name'                  => 'Integration Basic Coupon',
                'status'                => 'active',
                'priority'              => 5,
                'coupon_code'           => 'SAVE15',
                'coupon_description'    => 'Save fifteen percent',
                'coupon_discount_type'  => 'percent',
                'coupon_amount'         => 15,
                'usage_limit'           => 25,
                'free_shipping'         => false,
                'display_locations'     => ['cart', 'checkout'],
                'selected_products'     => [101],
                'selected_categories'   => [12],
            ],
            $overrides
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeTieredPricingPayload(array $overrides = []): array
    {
        return array_replace_recursive(
            [
                'module'                  => 'dynamic_pricing',
                'rule_type'               => 'tiered_pricing',
                'name'                    => 'Integration Tier Pricing',
                'status'                  => 'active',
                'priority'                => 10,
                'applies_to'              => 'all_products',
                'selected_products'       => [],
                'selected_categories'     => [],
                'excluded_products'       => [],
                'show_pricing_table'      => true,
                'badge_enabled'           => false,
                'badge_text'              => '',
                'savings_message_enabled' => false,
                'tiers'                   => [
                    [
                        'min_qty'        => 2,
                        'max_qty'        => 4,
                        'discount_type'  => 'percentage',
                        'discount_value' => 10,
                    ],
                    [
                        'min_qty'        => 5,
                        'max_qty'        => '',
                        'discount_type'  => 'fixed',
                        'discount_value' => 7,
                    ],
                ],
            ],
            $overrides
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeCartSubtotalDiscountPayload(array $overrides = []): array
    {
        return array_replace_recursive(
            [
                'module'                  => 'dynamic_pricing',
                'rule_type'               => 'cart_subtotal_discount',
                'name'                    => 'Integration Cart Discount',
                'status'                  => 'active',
                'priority'                => 10,
                'discount_type'           => 'fixed',
                'discount_value'          => 20,
                'min_cart_amount'         => 100,
                'max_cart_amount'         => '',
                'badge_enabled'           => false,
                'badge_text'              => '',
                'savings_message_enabled' => true,
            ],
            $overrides
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeBogoCouponPayload(array $overrides = []): array
    {
        return array_replace_recursive(
            [
                'module'                => 'coupon_engine',
                'rule_type'             => 'bogo_coupon',
                'name'                  => 'Integration BOGO Coupon',
                'status'                => 'active',
                'priority'              => 5,
                'coupon_code'           => 'BOGO1',
                'coupon_description'    => 'Buy one get one reward',
                'coupon_discount_type'  => 'fixed_cart',
                'coupon_amount'         => 0,
                'usage_limit'           => 10,
                'free_shipping'         => false,
                'display_locations'     => ['cart'],
                'buy_quantity'          => 2,
                'buy_product_id'        => 101,
                'get_product_id'        => 202,
                'reward_type'           => 'free',
                'discount_value'        => 0,
            ],
            $overrides
        );
    }
}
