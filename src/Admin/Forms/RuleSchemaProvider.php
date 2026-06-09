<?php

declare(strict_types=1);

namespace Pluginora\Admin\Forms;

final class RuleSchemaProvider
{
    public function getSchema(): array
    {
        return [
            'families' => [
                [
                    'slug'        => 'dynamic_pricing',
                    'label'       => __('Create Dynamic Pricing Rule', 'pluginora'),
                    'description' => __(
                        'Build product, quantity, cart, and scheduled discount rules through a guided flow.',
                        'pluginora'
                    ),
                ],
                [
                    'slug'        => 'coupon_engine',
                    'label'       => __('Create Coupon Rule', 'pluginora'),
                    'description' => __(
                        'Create native-backed coupon rules, auto-apply logic, and BOGO promotions.',
                        'pluginora'
                    ),
                ],
            ],
            'types'    => [
                'dynamic_pricing' => [
                    [
                        'slug'        => 'simple_discount',
                        'label'       => __('Percentage or Fixed Discount', 'pluginora'),
                        'description' => __(
                            'Apply a percentage or fixed discount to products or categories.',
                            'pluginora'
                        ),
                        'fields'      => $this->getSimpleDiscountFields(),
                    ],
                    [
                        'slug'        => 'tiered_pricing',
                        'label'       => __('Bulk or Tiered Pricing', 'pluginora'),
                        'description' => __(
                            'Define quantity ranges with discount tiers and an optional pricing table.',
                            'pluginora'
                        ),
                        'fields'      => $this->getTieredPricingFields(),
                    ],
                    [
                        'slug'        => 'cart_subtotal_discount',
                        'label'       => __('Cart Total Discount', 'pluginora'),
                        'description' => __(
                            'Trigger a discount when the cart subtotal reaches a threshold.',
                            'pluginora'
                        ),
                        'fields'      => $this->getCartSubtotalFields(),
                    ],
                ],
                'coupon_engine'   => [
                    [
                        'slug'        => 'basic_coupon',
                        'label'       => __('Basic Coupon', 'pluginora'),
                        'description' => __(
                            'Create a native WooCommerce coupon with core settings.',
                            'pluginora'
                        ),
                        'fields'      => $this->getBasicCouponFields(),
                    ],
                    [
                        'slug'        => 'auto_apply_coupon',
                        'label'       => __('Auto Apply Coupon', 'pluginora'),
                        'description' => __(
                            'Automatically apply a coupon when the cart matches the rule.',
                            'pluginora'
                        ),
                        'fields'      => $this->getAutoApplyCouponFields(),
                    ],
                    [
                        'slug'        => 'bogo_coupon',
                        'label'       => __('BOGO Coupon', 'pluginora'),
                        'description' => __(
                            'Set up Buy X Get Y free or discounted promotions.',
                            'pluginora'
                        ),
                        'fields'      => $this->getBogoFields(),
                    ],
                ],
            ],
            'defaults' => [
                'status'                   => 'inactive',
                'priority'                 => 1,
                'discount_type'            => 'percentage',
                'applies_to'               => 'all_products',
                'show_pricing_table'       => false,
                'badge_enabled'            => false,
                'badge_text'               => '',
                'savings_message_enabled'  => true,
                'coupon_discount_type'     => 'percent',
                'free_shipping'            => false,
                'display_locations'        => ['cart', 'checkout'],
                'reward_type'              => 'free',
                'buy_quantity'             => 1,
                'tiers'                    => [
                    [
                        'min_qty'        => 1,
                        'max_qty'        => 5,
                        'discount_type'  => 'percentage',
                        'discount_value' => 5,
                    ],
                ],
            ],
        ];
    }

    public function getType(string $module, string $ruleType): ?array
    {
        $types = $this->getSchema()['types'][$module] ?? [];

        foreach ($types as $type) {
            if ($type['slug'] === $ruleType) {
                return $type;
            }
        }

        return null;
    }

    private function getSharedFields(): array
    {
        return [
            [
                'key'     => 'name',
                'label'   => __('Rule Name', 'pluginora'),
                'type'    => 'text',
                'section' => 'basics',
                'required' => true,
            ],
            [
                'key'      => 'status',
                'label'    => __('Status', 'pluginora'),
                'type'     => 'select',
                'section'  => 'basics',
                'options'  => [
                    ['value' => 'active', 'label' => __('Active', 'pluginora')],
                    ['value' => 'inactive', 'label' => __('Inactive', 'pluginora')],
                ],
            ],
            [
                'key'      => 'priority',
                'label'    => __('Priority', 'pluginora'),
                'type'     => 'number',
                'section'  => 'basics',
                'min'      => 1,
                'help'     => __('Lower values run first when rules compete.', 'pluginora'),
            ],
            [
                'key'      => 'starts_at',
                'label'    => __('Start Date', 'pluginora'),
                'type'     => 'datetime',
                'section'  => 'schedule',
            ],
            [
                'key'      => 'ends_at',
                'label'    => __('End Date', 'pluginora'),
                'type'     => 'datetime',
                'section'  => 'schedule',
            ],
        ];
    }

    private function getSimpleDiscountFields(): array
    {
        return array_merge(
            $this->getSharedFields(),
            [
                [
                    'key'      => 'discount_type',
                    'label'    => __('Discount Type', 'pluginora'),
                    'type'     => 'select',
                    'section'  => 'discount',
                    'options'  => [
                        ['value' => 'percentage', 'label' => __('Percentage', 'pluginora')],
                        ['value' => 'fixed', 'label' => __('Fixed Amount', 'pluginora')],
                    ],
                ],
                [
                    'key'      => 'discount_value',
                    'label'    => __('Discount Value', 'pluginora'),
                    'type'     => 'number',
                    'section'  => 'discount',
                    'min'      => 0,
                    'step'     => '0.01',
                ],
                [
                    'key'      => 'applies_to',
                    'label'    => __('Applies To', 'pluginora'),
                    'type'     => 'select',
                    'section'  => 'targeting',
                    'options'  => [
                        ['value' => 'all_products', 'label' => __('All Products', 'pluginora')],
                        ['value' => 'selected_products', 'label' => __('Selected Products', 'pluginora')],
                        ['value' => 'selected_categories', 'label' => __('Selected Categories', 'pluginora')],
                    ],
                ],
                [
                    'key'         => 'selected_products',
                    'label'       => __('Selected Products', 'pluginora'),
                    'type'        => 'lookup-multi',
                    'section'     => 'targeting',
                    'lookup'      => 'products',
                    'depends_on'  => ['field' => 'applies_to', 'values' => ['selected_products']],
                ],
                [
                    'key'         => 'selected_categories',
                    'label'       => __('Selected Categories', 'pluginora'),
                    'type'        => 'lookup-multi',
                    'section'     => 'targeting',
                    'lookup'      => 'categories',
                    'depends_on'  => ['field' => 'applies_to', 'values' => ['selected_categories']],
                ],
                [
                    'key'      => 'excluded_products',
                    'label'    => __('Exclude Products', 'pluginora'),
                    'type'     => 'lookup-multi',
                    'section'  => 'targeting',
                    'lookup'   => 'products',
                ],
                [
                    'key'      => 'badge_enabled',
                    'label'    => __('Enable Sale Badge', 'pluginora'),
                    'type'     => 'checkbox',
                    'section'  => 'display',
                ],
                [
                    'key'         => 'badge_text',
                    'label'       => __('Custom Badge Text', 'pluginora'),
                    'type'        => 'text',
                    'section'     => 'display',
                    'depends_on'  => ['field' => 'badge_enabled', 'values' => [true]],
                ],
                [
                    'key'      => 'savings_message_enabled',
                    'label'    => __('Show You Saved Message', 'pluginora'),
                    'type'     => 'checkbox',
                    'section'  => 'display',
                ],
            ]
        );
    }

    private function getTieredPricingFields(): array
    {
        return array_merge(
            $this->getSharedFields(),
            [
                [
                    'key'      => 'applies_to',
                    'label'    => __('Applies To', 'pluginora'),
                    'type'     => 'select',
                    'section'  => 'targeting',
                    'options'  => [
                        ['value' => 'selected_products', 'label' => __('Selected Products', 'pluginora')],
                        ['value' => 'selected_categories', 'label' => __('Selected Categories', 'pluginora')],
                    ],
                ],
                [
                    'key'         => 'selected_products',
                    'label'       => __('Selected Products', 'pluginora'),
                    'type'        => 'lookup-multi',
                    'section'     => 'targeting',
                    'lookup'      => 'products',
                    'depends_on'  => ['field' => 'applies_to', 'values' => ['selected_products']],
                ],
                [
                    'key'         => 'selected_categories',
                    'label'       => __('Selected Categories', 'pluginora'),
                    'type'        => 'lookup-multi',
                    'section'     => 'targeting',
                    'lookup'      => 'categories',
                    'depends_on'  => ['field' => 'applies_to', 'values' => ['selected_categories']],
                ],
                [
                    'key'      => 'tiers',
                    'label'    => __('Quantity Tiers', 'pluginora'),
                    'type'     => 'tier-repeater',
                    'section'  => 'discount',
                ],
                [
                    'key'      => 'show_pricing_table',
                    'label'    => __('Show Pricing Table', 'pluginora'),
                    'type'     => 'checkbox',
                    'section'  => 'display',
                ],
            ]
        );
    }

    private function getCartSubtotalFields(): array
    {
        return array_merge(
            $this->getSharedFields(),
            [
                [
                    'key'      => 'min_cart_amount',
                    'label'    => __('Minimum Cart Amount', 'pluginora'),
                    'type'     => 'number',
                    'section'  => 'conditions',
                    'min'      => 0,
                    'step'     => '0.01',
                ],
                [
                    'key'      => 'max_cart_amount',
                    'label'    => __('Maximum Cart Amount', 'pluginora'),
                    'type'     => 'number',
                    'section'  => 'conditions',
                    'min'      => 0,
                    'step'     => '0.01',
                ],
                [
                    'key'      => 'discount_type',
                    'label'    => __('Discount Type', 'pluginora'),
                    'type'     => 'select',
                    'section'  => 'discount',
                    'options'  => [
                        ['value' => 'percentage', 'label' => __('Percentage', 'pluginora')],
                        ['value' => 'fixed', 'label' => __('Fixed Amount', 'pluginora')],
                    ],
                ],
                [
                    'key'      => 'discount_value',
                    'label'    => __('Discount Value', 'pluginora'),
                    'type'     => 'number',
                    'section'  => 'discount',
                    'min'      => 0,
                    'step'     => '0.01',
                ],
                [
                    'key'      => 'savings_message_enabled',
                    'label'    => __('Show Progress Notices', 'pluginora'),
                    'type'     => 'checkbox',
                    'section'  => 'display',
                ],
            ]
        );
    }

    private function getBasicCouponFields(): array
    {
        return array_merge(
            $this->getSharedFields(),
            [
                [
                    'key'      => 'coupon_code',
                    'label'    => __('Coupon Code', 'pluginora'),
                    'type'     => 'text',
                    'section'  => 'coupon',
                    'required' => true,
                ],
                [
                    'key'      => 'coupon_description',
                    'label'    => __('Description', 'pluginora'),
                    'type'     => 'textarea',
                    'section'  => 'coupon',
                ],
                [
                    'key'      => 'coupon_discount_type',
                    'label'    => __('Coupon Discount Type', 'pluginora'),
                    'type'     => 'select',
                    'section'  => 'coupon',
                    'options'  => [
                        ['value' => 'percent', 'label' => __('Percentage Discount', 'pluginora')],
                        ['value' => 'fixed_cart', 'label' => __('Fixed Cart Discount', 'pluginora')],
                        ['value' => 'free_shipping', 'label' => __('Free Shipping', 'pluginora')],
                    ],
                ],
                [
                    'key'      => 'coupon_amount',
                    'label'    => __('Amount', 'pluginora'),
                    'type'     => 'number',
                    'section'  => 'coupon',
                    'min'      => 0,
                    'step'     => '0.01',
                ],
                [
                    'key'      => 'usage_limit',
                    'label'    => __('Usage Limit', 'pluginora'),
                    'type'     => 'number',
                    'section'  => 'coupon',
                    'min'      => 0,
                ],
                [
                    'key'      => 'expiry_date',
                    'label'    => __('Expiry Date', 'pluginora'),
                    'type'     => 'datetime',
                    'section'  => 'coupon',
                ],
                [
                    'key'      => 'free_shipping',
                    'label'    => __('Allow Free Shipping', 'pluginora'),
                    'type'     => 'checkbox',
                    'section'  => 'coupon',
                ],
                [
                    'key'      => 'display_locations',
                    'label'    => __('Show Available Coupon On', 'pluginora'),
                    'type'     => 'checkbox-group',
                    'section'  => 'display',
                    'options'  => [
                        ['value' => 'cart', 'label' => __('Cart', 'pluginora')],
                        ['value' => 'checkout', 'label' => __('Checkout', 'pluginora')],
                        ['value' => 'myaccount', 'label' => __('My Account', 'pluginora')],
                    ],
                ],
            ]
        );
    }

    private function getAutoApplyCouponFields(): array
    {
        return array_merge(
            $this->getBasicCouponFields(),
            [
                [
                    'key'      => 'min_cart_amount',
                    'label'    => __('Minimum Cart Amount', 'pluginora'),
                    'type'     => 'number',
                    'section'  => 'conditions',
                    'min'      => 0,
                    'step'     => '0.01',
                ],
                [
                    'key'      => 'selected_products',
                    'label'    => __('Selected Products', 'pluginora'),
                    'type'     => 'lookup-multi',
                    'section'  => 'conditions',
                    'lookup'   => 'products',
                ],
                [
                    'key'      => 'selected_categories',
                    'label'    => __('Selected Categories', 'pluginora'),
                    'type'     => 'lookup-multi',
                    'section'  => 'conditions',
                    'lookup'   => 'categories',
                ],
            ]
        );
    }

    private function getBogoFields(): array
    {
        return array_merge(
            $this->getSharedFields(),
            [
                [
                    'key'      => 'coupon_code',
                    'label'    => __('Coupon Code', 'pluginora'),
                    'type'     => 'text',
                    'section'  => 'coupon',
                ],
                [
                    'key'      => 'buy_product_id',
                    'label'    => __('Buy Product', 'pluginora'),
                    'type'     => 'lookup-single',
                    'section'  => 'conditions',
                    'lookup'   => 'products',
                ],
                [
                    'key'      => 'buy_quantity',
                    'label'    => __('Buy Quantity', 'pluginora'),
                    'type'     => 'number',
                    'section'  => 'conditions',
                    'min'      => 1,
                ],
                [
                    'key'      => 'get_product_id',
                    'label'    => __('Get Product', 'pluginora'),
                    'type'     => 'lookup-single',
                    'section'  => 'conditions',
                    'lookup'   => 'products',
                ],
                [
                    'key'      => 'reward_type',
                    'label'    => __('Reward Type', 'pluginora'),
                    'type'     => 'select',
                    'section'  => 'discount',
                    'options'  => [
                        ['value' => 'free', 'label' => __('Get Product Free', 'pluginora')],
                        ['value' => 'percentage', 'label' => __('Get Product % Off', 'pluginora')],
                    ],
                ],
                [
                    'key'         => 'discount_value',
                    'label'       => __('Discount Amount', 'pluginora'),
                    'type'        => 'number',
                    'section'     => 'discount',
                    'min'         => 0,
                    'step'        => '0.01',
                    'depends_on'  => ['field' => 'reward_type', 'values' => ['percentage']],
                ],
                [
                    'key'      => 'display_locations',
                    'label'    => __('Show Available Coupon On', 'pluginora'),
                    'type'     => 'checkbox-group',
                    'section'  => 'display',
                    'options'  => [
                        ['value' => 'cart', 'label' => __('Cart', 'pluginora')],
                        ['value' => 'checkout', 'label' => __('Checkout', 'pluginora')],
                        ['value' => 'myaccount', 'label' => __('My Account', 'pluginora')],
                    ],
                ],
            ]
        );
    }
}
