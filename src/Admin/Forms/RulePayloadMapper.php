<?php

declare(strict_types=1);

namespace Pluginora\Admin\Forms;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Pluginora\Support\Rule;
use Pluginora\Support\RuleAction;
use Pluginora\Support\RuleCondition;
use Pluginora\Support\RuleItem;
use Pluginora\Support\RuleTier;

final class RulePayloadMapper
{
    public function __construct(private readonly RuleSchemaProvider $schemaProvider)
    {
    }

    public function fromPayload(array $payload, ?Rule $existingRule = null): Rule
    {
        $module   = sanitize_key((string) ($payload['module'] ?? ''));
        $ruleType = sanitize_key((string) ($payload['rule_type'] ?? ''));
        $type     = $this->schemaProvider->getType($module, $ruleType);

        if (null === $type) {
            throw new InvalidArgumentException(__('Invalid Pluginora rule type.', 'pluginora'));
        }

        $this->assertRequiredFields($payload, $type['fields']);

        $name = sanitize_text_field((string) ($payload['name'] ?? ''));

        if ('' === $name) {
            throw new InvalidArgumentException(__('Rule name is required.', 'pluginora'));
        }

        $status   = in_array($payload['status'] ?? 'inactive', ['active', 'inactive'], true)
            ? (string) $payload['status']
            : 'inactive';
        $priority = max(1, absint($payload['priority'] ?? 10));

        return new Rule(
            $existingRule?->getId(),
            $module,
            $ruleType,
            $name,
            $status,
            $priority,
            isset($payload['stack_mode_override']) ? sanitize_key((string) $payload['stack_mode_override']) : null,
            $this->toGmtDate($payload['starts_at'] ?? null),
            $this->toGmtDate($payload['ends_at'] ?? null),
            $this->buildConditions($payload, $module, $ruleType),
            $this->buildActions($payload, $module, $ruleType),
            $this->buildItems($payload, $module, $ruleType),
            $this->buildTiers($payload, $ruleType)
        );
    }

    public function toArray(Rule $rule): array
    {
        $conditions = [];

        foreach ($rule->getConditions() as $condition) {
            $conditions[$condition->getConditionType()] = $condition->getConditionValue();
        }

        $actions = [];

        foreach ($rule->getActions() as $action) {
            $actions[$action->getActionType()] = $action->getActionValue();
        }

        $items = [
            'selected_products'   => [],
            'selected_categories' => [],
            'excluded_products'   => [],
            'buy_product_id'      => null,
            'get_product_id'      => null,
        ];

        foreach ($rule->getItems() as $item) {
            switch ($item->getObjectType()) {
                case 'product':
                    $items['selected_products'][] = $item->getObjectId();
                    break;
                case 'category':
                    $items['selected_categories'][] = $item->getObjectId();
                    break;
                case 'excluded_product':
                    $items['excluded_products'][] = $item->getObjectId();
                    break;
                case 'buy_product':
                    $items['buy_product_id'] = $item->getObjectId();
                    break;
                case 'get_product':
                    $items['get_product_id'] = $item->getObjectId();
                    break;
            }
        }

        return array_merge(
            [
                'id'                  => $rule->getId(),
                'module'              => $rule->getModule(),
                'rule_type'           => $rule->getRuleType(),
                'name'                => $rule->getName(),
                'status'              => $rule->getStatus(),
                'priority'            => $rule->getPriority(),
                'starts_at'           => $this->fromGmtDate($rule->getStartsAtGmt()),
                'ends_at'             => $this->fromGmtDate($rule->getEndsAtGmt()),
                'stack_mode_override' => $rule->getStackModeOverride(),
                'tiers'               => array_map(
                    static fn (RuleTier $tier): array => [
                        'min_qty'        => $tier->getMinQuantity(),
                        'max_qty'        => $tier->getMaxQuantity(),
                        'discount_type'  => $tier->getDiscountType(),
                        'discount_value' => $tier->getDiscountValue(),
                    ],
                    $rule->getTiers()
                ),
            ],
            $conditions,
            $actions,
            $items
        );
    }

    /**
     * @return RuleCondition[]
     */
    private function buildConditions(array $payload, string $module, string $ruleType): array
    {
        $conditions = [];

        if (
            'dynamic_pricing' === $module
            && in_array($ruleType, ['simple_discount', 'tiered_pricing'], true)
        ) {
            $conditions[] = new RuleCondition(
                'applies_to',
                '=',
                $this->sanitizeSelection(
                    $payload['applies_to'] ?? 'all_products',
                    ['all_products', 'selected_products', 'selected_categories']
                )
            );
        }

        if ('cart_subtotal_discount' === $ruleType || 'auto_apply_coupon' === $ruleType) {
            if ('' !== (string) ($payload['min_cart_amount'] ?? '')) {
                $conditions[] = new RuleCondition('min_cart_amount', '>=', (float) $payload['min_cart_amount']);
            }

            if ('' !== (string) ($payload['max_cart_amount'] ?? '')) {
                $conditions[] = new RuleCondition('max_cart_amount', '<=', (float) $payload['max_cart_amount']);
            }
        }

        if ('bogo_coupon' === $ruleType) {
            $conditions[] = new RuleCondition('buy_quantity', '>=', max(1, absint($payload['buy_quantity'] ?? 1)));
        }

        return $conditions;
    }

    /**
     * @return RuleAction[]
     */
    private function buildActions(array $payload, string $module, string $ruleType): array
    {
        $actions = [];

        if (in_array($ruleType, ['simple_discount', 'cart_subtotal_discount'], true)) {
            $actions[] = new RuleAction(
                'discount_type',
                $this->sanitizeSelection($payload['discount_type'] ?? 'percentage', ['percentage', 'fixed'])
            );
            $actions[] = new RuleAction('discount_value', (float) ($payload['discount_value'] ?? 0));
        }

        if ('tiered_pricing' === $ruleType) {
            $actions[] = new RuleAction('show_pricing_table', ! empty($payload['show_pricing_table']));
        }

        if ('dynamic_pricing' === $module) {
            $actions[] = new RuleAction('badge_enabled', ! empty($payload['badge_enabled']));
            $actions[] = new RuleAction('badge_text', sanitize_text_field((string) ($payload['badge_text'] ?? '')));
            $actions[] = new RuleAction('savings_message_enabled', ! empty($payload['savings_message_enabled']));
        }

        if (in_array($ruleType, ['basic_coupon', 'auto_apply_coupon', 'bogo_coupon'], true)) {
            $actions[] = new RuleAction(
                'coupon_code',
                sanitize_text_field((string) ($payload['coupon_code'] ?? ''))
            );
            $actions[] = new RuleAction(
                'coupon_description',
                sanitize_textarea_field((string) ($payload['coupon_description'] ?? ''))
            );
            $actions[] = new RuleAction(
                'coupon_discount_type',
                $this->sanitizeSelection(
                    $payload['coupon_discount_type'] ?? 'percent',
                    ['percent', 'fixed_cart', 'free_shipping']
                )
            );
            $actions[] = new RuleAction('coupon_amount', (float) ($payload['coupon_amount'] ?? 0));
            $actions[] = new RuleAction('usage_limit', absint($payload['usage_limit'] ?? 0));
            $actions[] = new RuleAction('expiry_date', $this->toGmtDate($payload['expiry_date'] ?? null));
            $actions[] = new RuleAction('free_shipping', ! empty($payload['free_shipping']));
        }

        if (in_array($ruleType, ['basic_coupon', 'auto_apply_coupon', 'bogo_coupon'], true)) {
            $actions[] = new RuleAction(
                'display_locations',
                $this->sanitizeStringArray($payload['display_locations'] ?? [])
            );
        }

        if ('bogo_coupon' === $ruleType) {
            $actions[] = new RuleAction(
                'reward_type',
                $this->sanitizeSelection($payload['reward_type'] ?? 'free', ['free', 'percentage'])
            );
            $actions[] = new RuleAction('discount_value', (float) ($payload['discount_value'] ?? 0));
        }

        return $actions;
    }

    /**
     * @return RuleItem[]
     */
    private function buildItems(array $payload, string $module, string $ruleType): array
    {
        $items = [];

        if ('dynamic_pricing' === $module || in_array($ruleType, ['basic_coupon', 'auto_apply_coupon'], true)) {
            foreach ($this->sanitizeIdArray($payload['selected_products'] ?? []) as $productId) {
                $items[] = new RuleItem('product', $productId);
            }

            foreach ($this->sanitizeIdArray($payload['selected_categories'] ?? []) as $categoryId) {
                $items[] = new RuleItem('category', $categoryId);
            }

            foreach ($this->sanitizeIdArray($payload['excluded_products'] ?? []) as $productId) {
                $items[] = new RuleItem('excluded_product', $productId);
            }
        }

        if ('bogo_coupon' === $ruleType) {
            $buyProductId = absint($payload['buy_product_id'] ?? 0);
            $getProductId = absint($payload['get_product_id'] ?? 0);

            if ($buyProductId > 0) {
                $items[] = new RuleItem('buy_product', $buyProductId);
            }

            if ($getProductId > 0) {
                $items[] = new RuleItem('get_product', $getProductId);
            }
        }

        return $items;
    }

    /**
     * @return RuleTier[]
     */
    private function buildTiers(array $payload, string $ruleType): array
    {
        if ('tiered_pricing' !== $ruleType || empty($payload['tiers']) || ! is_array($payload['tiers'])) {
            return [];
        }

        $tiers = [];

        foreach ($payload['tiers'] as $tier) {
            if (! is_array($tier)) {
                continue;
            }

            $minQuantity = max(1, absint($tier['min_qty'] ?? 1));
            $maxQuantity = '' === (string) ($tier['max_qty'] ?? '') ? null : absint($tier['max_qty']);

            $tiers[] = new RuleTier(
                $minQuantity,
                $maxQuantity,
                $this->sanitizeSelection($tier['discount_type'] ?? 'percentage', ['percentage', 'fixed']),
                (float) ($tier['discount_value'] ?? 0)
            );
        }

        return $tiers;
    }

    private function assertRequiredFields(array $payload, array $fields): void
    {
        foreach ($fields as $field) {
            if (empty($field['required'])) {
                continue;
            }

            $value = $payload[$field['key']] ?? null;

            if ($this->isEmptyValue($value)) {
                throw new InvalidArgumentException(
                    sprintf(
                        /* translators: 1: field label. */
                        __('%1$s is required.', 'pluginora'),
                        $field['label']
                    )
                );
            }
        }
    }

    private function isEmptyValue(mixed $value): bool
    {
        if (is_array($value)) {
            return [] === $value;
        }

        return null === $value || '' === trim((string) $value);
    }

    private function sanitizeSelection(mixed $value, array $allowed): string
    {
        $value = sanitize_key((string) $value);

        return in_array($value, $allowed, true) ? $value : $allowed[0];
    }

    private function sanitizeIdArray(mixed $value): array
    {
        $values = is_array($value) ? $value : [];

        return array_values(array_filter(array_map('absint', $values)));
    }

    private function sanitizeStringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    static fn (mixed $item): string => sanitize_key((string) $item),
                    $value
                )
            )
        );
    }

    private function toGmtDate(mixed $value): ?string
    {
        if (! is_string($value) || '' === trim($value)) {
            return null;
        }

        $timezone = wp_timezone();
        $value    = trim($value);
        $date     = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, $timezone);

        if (false === $date) {
            $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, $timezone);
        }

        if (false === $date) {
            $date = date_create_immutable($value, $timezone);
        }

        if (false === $date) {
            return null;
        }

        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function fromGmtDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $timezone = wp_timezone();
        $date     = new DateTimeImmutable($value, new DateTimeZone('UTC'));

        return $date->setTimezone($timezone)->format('Y-m-d\TH:i');
    }
}
