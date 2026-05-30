<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Infrastructure;

use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Repository\Contracts\RuleQueryRepositoryInterface;
use Pluginora\Repository\Contracts\RuleRepositoryInterface;
use Pluginora\Support\Rule;

final class ScheduledRuleProcessor implements HookableInterface
{
    public function __construct(
        private readonly RuleQueryRepositoryInterface $ruleQueryRepository,
        private readonly RuleRepositoryInterface $ruleRepository
    ) {
    }

    public function register(): void
    {
        add_action('init', [$this, 'schedule']);
        add_action('pluginora_process_scheduled_rules', [$this, 'process']);
    }

    public function schedule(): void
    {
        if (! wp_next_scheduled('pluginora_process_scheduled_rules')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'pluginora_process_scheduled_rules');
        }
    }

    public function process(): void
    {
        $now = gmdate('Y-m-d H:i:s');

        foreach ($this->ruleQueryRepository->findByFilters(['module' => 'dynamic_pricing']) as $rule) {
            $this->processRule($rule, $now);
        }
    }

    private function processRule(Rule $rule, string $now): void
    {
        $startsAt = $rule->getStartsAtGmt();
        $endsAt   = $rule->getEndsAtGmt();

        if (empty($startsAt) && empty($endsAt)) {
            return;
        }

        $shouldBeActive = true;

        if (! empty($startsAt) && $now < $startsAt) {
            $shouldBeActive = false;
        }

        if (! empty($endsAt) && $now > $endsAt) {
            $shouldBeActive = false;
        }

        $targetStatus = $shouldBeActive ? 'active' : 'inactive';

        if ($rule->getStatus() !== $targetStatus && null !== $rule->getId()) {
            $this->ruleRepository->updateStatus($rule->getId(), $targetStatus);
        }
    }
}
