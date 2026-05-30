<?php

declare(strict_types=1);

namespace Pluginora\Core\Settings;

final class SettingsRepository
{
    public const OPTION_KEY = 'pluginora_settings';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $settings = get_option(self::OPTION_KEY, []);

        if (! is_array($settings)) {
            $settings = [];
        }

        return wp_parse_args($settings, self::defaults());
    }

    public function getConflictMode(): string
    {
        $mode = sanitize_key((string) ($this->all()['conflict_mode'] ?? 'best_discount_only'));

        return in_array($mode, array_keys($this->getConflictModeOptions()), true)
            ? $mode
            : 'best_discount_only';
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function sanitize(array $settings): array
    {
        $mode = sanitize_key((string) ($settings['conflict_mode'] ?? 'best_discount_only'));

        if (! in_array($mode, array_keys($this->getConflictModeOptions()), true)) {
            $mode = 'best_discount_only';
        }

        return [
            'conflict_mode' => $mode,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getConflictModeOptions(): array
    {
        return [
            'stack_all'          => __('Stack All Discounts', 'pluginora'),
            'best_discount_only' => __('Best Discount Only', 'pluginora'),
            'coupon_priority'    => __('Coupon Priority', 'pluginora'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'conflict_mode' => 'best_discount_only',
        ];
    }
}
