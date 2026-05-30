<?php

declare(strict_types=1);

namespace Pluginora\Repository\Wpdb;

use Pluginora\Core\Database\RuleTables;
use Pluginora\Repository\Contracts\RuleQueryRepositoryInterface;
use Pluginora\Support\Rule;
use wpdb;

final class WpdbRuleQueryRepository implements RuleQueryRepositoryInterface
{
    public function __construct(
        private readonly wpdb $wpdb,
        private readonly RuleTables $tables,
        private readonly WpdbRuleRepository $ruleRepository
    ) {
    }

    public function findActiveByModule(string $module, ?string $currentGmt = null): array
    {
        $timestamp = $currentGmt ?? gmdate('Y-m-d H:i:s');

        $sql = $this->wpdb->prepare(
            "SELECT id
            FROM {$this->tables->rules()}
            WHERE module = %s
                AND status = 'active'
                AND (starts_at_gmt IS NULL OR starts_at_gmt <= %s)
                AND (ends_at_gmt IS NULL OR ends_at_gmt >= %s)
            ORDER BY priority ASC, id ASC",
            $module,
            $timestamp,
            $timestamp
        );

        return $this->mapRules($this->wpdb->get_col($sql));
    }

    public function findByFilters(array $filters = []): array
    {
        $whereClauses = ['1=1'];
        $parameters   = [];

        if (! empty($filters['module'])) {
            $whereClauses[] = 'module = %s';
            $parameters[]   = (string) $filters['module'];
        }

        if (! empty($filters['status'])) {
            $whereClauses[] = 'status = %s';
            $parameters[]   = (string) $filters['status'];
        }

        if (! empty($filters['rule_type'])) {
            $whereClauses[] = 'rule_type = %s';
            $parameters[]   = (string) $filters['rule_type'];
        }

        $sql = sprintf(
            'SELECT id FROM %s WHERE %s ORDER BY priority ASC, id DESC',
            $this->tables->rules(),
            implode(' AND ', $whereClauses)
        );

        if ([] !== $parameters) {
            $sql = $this->wpdb->prepare($sql, ...$parameters);
        }

        return $this->mapRules($this->wpdb->get_col($sql));
    }

    private function mapRules(array $ruleIds): array
    {
        $rules = [];

        foreach ($ruleIds as $ruleId) {
            $rule = $this->ruleRepository->find((int) $ruleId);

            if (null !== $rule) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }
}
