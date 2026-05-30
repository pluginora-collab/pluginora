<?php

declare(strict_types=1);

namespace Pluginora\Modules\DynamicPricing\Application;

use Pluginora\Support\Rule;
use Pluginora\Support\RuleAction;
use Pluginora\Support\RuleCondition;
use Pluginora\Support\RuleItem;

final class RuleDataAccessor
{
    public function getConditionValue(Rule $rule, string $conditionType, mixed $default = null): mixed
    {
        foreach ($rule->getConditions() as $condition) {
            if ($condition instanceof RuleCondition && $condition->getConditionType() === $conditionType) {
                return $condition->getConditionValue();
            }
        }

        return $default;
    }

    public function getActionValue(Rule $rule, string $actionType, mixed $default = null): mixed
    {
        foreach ($rule->getActions() as $action) {
            if ($action instanceof RuleAction && $action->getActionType() === $actionType) {
                return $action->getActionValue();
            }
        }

        return $default;
    }

    /**
     * @return int[]
     */
    public function getItemIds(Rule $rule, string $objectType): array
    {
        $ids = [];

        foreach ($rule->getItems() as $item) {
            if ($item instanceof RuleItem && $item->getObjectType() === $objectType) {
                $ids[] = $item->getObjectId();
            }
        }

        return $ids;
    }
}
