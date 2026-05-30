<?php

declare(strict_types=1);

namespace Pluginora\Repository\Wpdb;

use Pluginora\Core\Database\RuleTables;
use Pluginora\Repository\Contracts\RuleRepositoryInterface;
use Pluginora\Support\Rule;
use Pluginora\Support\RuleAction;
use Pluginora\Support\RuleCondition;
use Pluginora\Support\RuleItem;
use Pluginora\Support\RuleTier;
use RuntimeException;
use wpdb;

final class WpdbRuleRepository implements RuleRepositoryInterface
{
    public function __construct(
        private readonly wpdb $wpdb,
        private readonly RuleTables $tables
    ) {
    }

    public function find(int $ruleId): ?Rule
    {
        $ruleRow = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables->rules()} WHERE id = %d",
                $ruleId
            ),
            ARRAY_A
        );

        if (! is_array($ruleRow)) {
            return null;
        }

        return Rule::fromRow(
            $ruleRow,
            $this->getConditions($ruleId),
            $this->getActions($ruleId),
            $this->getItems($ruleId),
            $this->getTiers($ruleId)
        );
    }

    public function save(Rule $rule): int
    {
        $data = $rule->toDatabaseRow();

        if (null !== $rule->getId()) {
            unset($data['created_at_gmt']);

            $result = $this->wpdb->update(
                $this->tables->rules(),
                $data,
                ['id' => $rule->getId()],
                ['%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s'],
                ['%d']
            );

            if (false === $result) {
                throw new RuntimeException('Failed to update Pluginora rule.');
            }

            $ruleId = $rule->getId();
            $this->deleteChildren($ruleId);
            $this->insertChildren($ruleId, $rule);

            $savedRule = $this->find($ruleId);

            if (null !== $savedRule) {
                do_action('pluginora_rule_saved', $savedRule);
            }

            return $ruleId;
        }

        $inserted = $this->wpdb->insert(
            $this->tables->rules(),
            $data,
            ['%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        if (! $inserted) {
            throw new RuntimeException('Failed to insert Pluginora rule.');
        }

        $ruleId = (int) $this->wpdb->insert_id;
        $this->insertChildren($ruleId, $rule);

        $savedRule = $this->find($ruleId);

        if (null !== $savedRule) {
            do_action('pluginora_rule_saved', $savedRule);
        }

        return $ruleId;
    }

    public function delete(int $ruleId): bool
    {
        $rule = $this->find($ruleId);

        $this->deleteChildren($ruleId);

        $deleted = $this->wpdb->delete(
            $this->tables->rules(),
            ['id' => $ruleId],
            ['%d']
        );

        if (false !== $deleted && null !== $rule) {
            do_action('pluginora_rule_deleted', $rule);
        }

        return false !== $deleted;
    }

    public function duplicate(int $ruleId): int
    {
        $rule = $this->find($ruleId);

        if (null === $rule) {
            throw new RuntimeException('Unable to duplicate a missing Pluginora rule.');
        }

        return $this->save($rule->duplicate());
    }

    public function updateStatus(int $ruleId, string $status): bool
    {
        $updated = $this->wpdb->update(
            $this->tables->rules(),
            [
                'status'         => $status,
                'updated_at_gmt' => gmdate('Y-m-d H:i:s'),
            ],
            ['id' => $ruleId],
            ['%s', '%s'],
            ['%d']
        );

        if (false !== $updated) {
            $rule = $this->find($ruleId);

            if (null !== $rule) {
                do_action('pluginora_rule_status_updated', $rule);
            }
        }

        return false !== $updated;
    }

    /**
     * @return RuleCondition[]
     */
    private function getConditions(int $ruleId): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables->conditions()} WHERE rule_id = %d ORDER BY sort_order ASC, id ASC",
                $ruleId
            ),
            ARRAY_A
        );

        return array_map(static fn (array $row): RuleCondition => RuleCondition::fromRow($row), $rows ?: []);
    }

    /**
     * @return RuleAction[]
     */
    private function getActions(int $ruleId): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables->actions()} WHERE rule_id = %d ORDER BY sort_order ASC, id ASC",
                $ruleId
            ),
            ARRAY_A
        );

        return array_map(static fn (array $row): RuleAction => RuleAction::fromRow($row), $rows ?: []);
    }

    /**
     * @return RuleItem[]
     */
    private function getItems(int $ruleId): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables->items()} WHERE rule_id = %d ORDER BY id ASC",
                $ruleId
            ),
            ARRAY_A
        );

        return array_map(static fn (array $row): RuleItem => RuleItem::fromRow($row), $rows ?: []);
    }

    /**
     * @return RuleTier[]
     */
    private function getTiers(int $ruleId): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->tables->tiers()} WHERE rule_id = %d ORDER BY min_qty ASC, id ASC",
                $ruleId
            ),
            ARRAY_A
        );

        return array_map(static fn (array $row): RuleTier => RuleTier::fromRow($row), $rows ?: []);
    }

    private function deleteChildren(int $ruleId): void
    {
        $this->wpdb->delete($this->tables->conditions(), ['rule_id' => $ruleId], ['%d']);
        $this->wpdb->delete($this->tables->actions(), ['rule_id' => $ruleId], ['%d']);
        $this->wpdb->delete($this->tables->items(), ['rule_id' => $ruleId], ['%d']);
        $this->wpdb->delete($this->tables->tiers(), ['rule_id' => $ruleId], ['%d']);
    }

    private function insertChildren(int $ruleId, Rule $rule): void
    {
        foreach ($rule->getConditions() as $condition) {
            $this->insertOrFail(
                $this->tables->conditions(),
                $condition->toDatabaseRow($ruleId),
                ['%d', '%s', '%s', '%s', '%d']
            );
        }

        foreach ($rule->getActions() as $action) {
            $this->insertOrFail(
                $this->tables->actions(),
                $action->toDatabaseRow($ruleId),
                ['%d', '%s', '%s', '%d']
            );
        }

        foreach ($rule->getItems() as $item) {
            $this->insertOrFail(
                $this->tables->items(),
                $item->toDatabaseRow($ruleId),
                ['%d', '%s', '%d']
            );
        }

        foreach ($rule->getTiers() as $tier) {
            $this->insertOrFail(
                $this->tables->tiers(),
                $tier->toDatabaseRow($ruleId),
                ['%d', '%d', '%d', '%s', '%f']
            );
        }
    }

    private function insertOrFail(string $table, array $data, array $formats): void
    {
        $inserted = $this->wpdb->insert($table, $data, $formats);

        if (! $inserted) {
            throw new RuntimeException(sprintf('Failed to insert Pluginora child row into "%s".', $table));
        }
    }
}
