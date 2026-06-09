<?php

declare(strict_types=1);

namespace Pluginora\Modules\CouponEngine\Application;

use Pluginora\Support\Rule;
use WC_Coupon;
use wpdb;

final class NativeCouponSyncService
{
    public const META_RULE_ID = '_pluginora_rule_id';

    public const META_MANAGED = '_pluginora_managed_coupon';

    public const META_RULE_TYPE = '_pluginora_rule_type';

    public function __construct(private readonly RuleDataAccessor $ruleDataAccessor)
    {
    }

    public function sync(Rule $rule): void
    {
        if ('coupon_engine' !== $rule->getModule()) {
            return;
        }

        $couponCode = sanitize_text_field((string) $this->ruleDataAccessor->getActionValue($rule, 'coupon_code', ''));

        if ('' === $couponCode) {
            return;
        }

        $coupon = $this->findCouponForRule($rule);

        $coupon->set_code($couponCode);
        $coupon->set_description((string) $this->ruleDataAccessor->getActionValue($rule, 'coupon_description', ''));
        $coupon->set_usage_limit((int) $this->ruleDataAccessor->getActionValue($rule, 'usage_limit', 0));
        $coupon->set_date_expires($this->getExpiryDate($rule));
        $coupon->set_free_shipping($this->shouldEnableFreeShipping($rule));
        $coupon->set_discount_type($this->resolveDiscountType($rule));
        $coupon->set_amount((string) $this->resolveAmount($rule));
        $coupon->set_product_ids($this->ruleDataAccessor->getItemIds($rule, 'product'));
        $coupon->set_product_categories($this->ruleDataAccessor->getItemIds($rule, 'category'));

        $couponId = $coupon->save();

        update_post_meta($couponId, self::META_RULE_ID, $rule->getId());
        update_post_meta($couponId, self::META_MANAGED, 'yes');
        update_post_meta($couponId, self::META_RULE_TYPE, $rule->getRuleType());

        wp_update_post(
            [
                'ID'          => $couponId,
                'post_status' => 'active' === $rule->getStatus() ? 'publish' : 'draft',
            ]
        );
    }

    public function delete(Rule $rule): void
    {
        $coupon = $this->findCouponForRule($rule);

        if ($coupon->get_id() > 0) {
            wp_trash_post($coupon->get_id());
        }
    }

    public function setStatus(Rule $rule): void
    {
        $coupon = $this->findCouponForRule($rule);

        if ($coupon->get_id() <= 0) {
            return;
        }

        wp_update_post(
            [
                'ID'          => $coupon->get_id(),
                'post_status' => 'active' === $rule->getStatus() ? 'publish' : 'draft',
            ]
        );
    }

    public function findCouponForRule(Rule $rule): WC_Coupon
    {
        if (null !== $rule->getId()) {
            $existingCouponId = $this->findCouponIdByRuleId($rule->getId());

            if ($existingCouponId > 0) {
                return new WC_Coupon($existingCouponId);
            }
        }

        $couponCode = sanitize_text_field((string) $this->ruleDataAccessor->getActionValue($rule, 'coupon_code', ''));

        if ('' !== $couponCode) {
            $coupon = $this->findCouponByCode($couponCode);

            if ($coupon instanceof WC_Coupon && $coupon->get_id() > 0) {
                return $coupon;
            }
        }

        return new WC_Coupon();
    }

    public function findCouponByCode(string $couponCode): ?WC_Coupon
    {
        $couponId = $this->findCouponIdByCode($couponCode);

        return $couponId > 0 ? new WC_Coupon($couponId) : null;
    }

    private function findCouponIdByRuleId(int $ruleId): int
    {
        $couponIds = get_posts(
            [
                'post_type'      => 'shop_coupon',
                'post_status'    => ['publish', 'draft', 'pending', 'private', 'trash'],
                'fields'         => 'ids',
                'posts_per_page' => 1,
                'meta_query'     => [
                    [
                        'key'   => self::META_RULE_ID,
                        'value' => $ruleId,
                    ],
                ],
            ]
        );

        return ! empty($couponIds) ? (int) $couponIds[0] : 0;
    }

    private function findCouponIdByCode(string $couponCode): int
    {
        global $wpdb;

        if (! $wpdb instanceof wpdb) {
            return 0;
        }

        $normalizedCode = function_exists('wc_format_coupon_code')
            ? wc_format_coupon_code($couponCode)
            : strtolower(sanitize_text_field($couponCode));

        $statuses = ['publish', 'draft', 'pending', 'private', 'trash'];
        $placeholders = implode(', ', array_fill(0, count($statuses), '%s'));
        $query = $wpdb->prepare(
            "SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = %s
                AND post_title = %s
                AND post_status IN ({$placeholders})
            ORDER BY ID DESC
            LIMIT 1",
            'shop_coupon',
            $normalizedCode,
            ...$statuses
        );

        if (! is_string($query)) {
            return 0;
        }

        return (int) $wpdb->get_var($query);
    }

    private function resolveDiscountType(Rule $rule): string
    {
        if ('bogo_coupon' === $rule->getRuleType()) {
            return 'fixed_cart';
        }

        $discountType = (string) $this->ruleDataAccessor->getActionValue($rule, 'coupon_discount_type', 'percent');

        if ('free_shipping' === $discountType) {
            return 'fixed_cart';
        }

        return $discountType;
    }

    private function resolveAmount(Rule $rule): float
    {
        if ('bogo_coupon' === $rule->getRuleType()) {
            return 0.0;
        }

        return (float) $this->ruleDataAccessor->getActionValue($rule, 'coupon_amount', 0);
    }

    private function getExpiryDate(Rule $rule): ?string
    {
        $expiry = $this->ruleDataAccessor->getActionValue($rule, 'expiry_date');

        if (empty($expiry) || ! is_string($expiry)) {
            return null;
        }

        return $expiry;
    }

    private function shouldEnableFreeShipping(Rule $rule): bool
    {
        $discountType = (string) $this->ruleDataAccessor->getActionValue($rule, 'coupon_discount_type', 'percent');

        return 'free_shipping' === $discountType
            || (bool) $this->ruleDataAccessor->getActionValue($rule, 'free_shipping', false);
    }
}
