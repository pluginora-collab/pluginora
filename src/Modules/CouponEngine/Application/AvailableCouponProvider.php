<?php

declare(strict_types=1);

namespace Pluginora\Modules\CouponEngine\Application;

use Pluginora\Repository\Contracts\RuleQueryRepositoryInterface;
use Pluginora\Support\Rule;

final class AvailableCouponProvider
{
    /**
     * @var Rule[]|null
     */
    private ?array $cachedRules = null;

    public function __construct(
        private readonly RuleQueryRepositoryInterface $ruleQueryRepository,
        private readonly RuleDataAccessor $ruleDataAccessor
    ) {
    }

    /**
     * @return Rule[]
     */
    public function getForLocation(string $location): array
    {
        $rules = [];

        foreach ($this->getActiveRules() as $rule) {
            $couponCode = (string) $this->ruleDataAccessor->getActionValue($rule, 'coupon_code', '');

            if ('' === $couponCode) {
                continue;
            }

            $locations = $this->ruleDataAccessor->getActionValue($rule, 'display_locations', ['cart', 'checkout']);

            if (! is_array($locations) || ! in_array($location, $locations, true)) {
                continue;
            }

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @return Rule[]
     */
    private function getActiveRules(): array
    {
        if (null === $this->cachedRules) {
            $this->cachedRules = $this->ruleQueryRepository->findActiveByModule('coupon_engine');
        }

        return $this->cachedRules;
    }
}
