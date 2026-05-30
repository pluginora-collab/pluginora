<?php

declare(strict_types=1);

namespace Pluginora\Modules\CouponEngine\Infrastructure;

use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Modules\CouponEngine\Application\NativeCouponSyncService;
use Pluginora\Support\Rule;

final class CouponRuleSyncHooks implements HookableInterface
{
    public function __construct(private readonly NativeCouponSyncService $nativeCouponSyncService)
    {
    }

    public function register(): void
    {
        add_action('pluginora_rule_saved', [$this, 'syncRule']);
        add_action('pluginora_rule_deleted', [$this, 'deleteRuleCoupon']);
        add_action('pluginora_rule_status_updated', [$this, 'syncRuleStatus']);
    }

    public function syncRule(Rule $rule): void
    {
        $this->nativeCouponSyncService->sync($rule);
    }

    public function deleteRuleCoupon(Rule $rule): void
    {
        $this->nativeCouponSyncService->delete($rule);
    }

    public function syncRuleStatus(Rule $rule): void
    {
        $this->nativeCouponSyncService->setStatus($rule);
    }
}
