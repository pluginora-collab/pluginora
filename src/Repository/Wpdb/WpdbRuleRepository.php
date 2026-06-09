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
use Throwable;
use wpdb;

final class WpdbRuleRepository implements RuleRepositoryInterface
{
    private ?array $pendingLogEntry = null;

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

        $this->beginTransaction();

        try {
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
                    $this->queueDatabaseError(
                        $rule->getId(),
                        'rule_update',
                        $this->tables->rules(),
                        'Failed to update Pluginora rule.',
                        $data
                    );

                    throw new RuntimeException('Failed to update Pluginora rule.');
                }

                $ruleId = $rule->getId();
                $this->deleteChildren($ruleId);
                $this->insertChildren($ruleId, $rule);
                $this->commitTransaction();

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
                $this->queueDatabaseError(
                    0,
                    'rule_insert',
                    $this->tables->rules(),
                    'Failed to insert Pluginora rule.',
                    $data
                );

                throw new RuntimeException('Failed to insert Pluginora rule.');
            }

            $ruleId = (int) $this->wpdb->insert_id;
            $this->insertChildren($ruleId, $rule);
            $this->commitTransaction();

            $savedRule = $this->find($ruleId);

            if (null !== $savedRule) {
                do_action('pluginora_rule_saved', $savedRule);
            }

            return $ruleId;
        } catch (Throwable $exception) {
            $this->rollbackTransaction();
            $this->flushQueuedDatabaseError();

            throw $exception;
        }
    }

    public function delete(int $ruleId): bool
    {
        $rule = $this->find($ruleId);

        $this->beginTransaction();

        try {
            $this->deleteChildren($ruleId);

            $deleted = $this->wpdb->delete(
                $this->tables->rules(),
                ['id' => $ruleId],
                ['%d']
            );

            if (false === $deleted) {
                $this->queueDatabaseError(
                    $ruleId,
                    'rule_delete',
                    $this->tables->rules(),
                    'Failed to delete Pluginora rule.',
                    ['rule_id' => $ruleId]
                );

                throw new RuntimeException('Failed to delete Pluginora rule.');
            }

            $this->commitTransaction();
        } catch (Throwable $exception) {
            $this->rollbackTransaction();
            $this->flushQueuedDatabaseError();

            throw $exception;
        }

        if (null !== $rule) {
            do_action('pluginora_rule_deleted', $rule);
        }

        return true;
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

        if (false === $updated) {
            $this->logDatabaseError(
                $ruleId,
                'rule_status_update',
                $this->tables->rules(),
                'Failed to update Pluginora rule status.',
                [
                    'rule_id' => $ruleId,
                    'status'  => $status,
                ]
            );
        }

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

        return array_map(static fn (array $row): RuleCondition => RuleCondition::fromRow($row), $rows);
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

        return array_map(static fn (array $row): RuleAction => RuleAction::fromRow($row), $rows);
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

        return array_map(static fn (array $row): RuleItem => RuleItem::fromRow($row), $rows);
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

        return array_map(static fn (array $row): RuleTier => RuleTier::fromRow($row), $rows);
    }

    private function deleteChildren(int $ruleId): void
    {
        $this->deleteChildrenFromTable($this->tables->conditions(), $ruleId);
        $this->deleteChildrenFromTable($this->tables->actions(), $ruleId);
        $this->deleteChildrenFromTable($this->tables->items(), $ruleId);
        $this->deleteChildrenFromTable($this->tables->tiers(), $ruleId);
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
            $this->queueDatabaseError(
                (int) ($data['rule_id'] ?? 0),
                'child_insert',
                $table,
                sprintf('Failed to insert Pluginora child row into "%s".', $table),
                $data
            );

            throw new RuntimeException(sprintf('Failed to insert Pluginora child row into "%s".', $table));
        }
    }

    private function deleteChildrenFromTable(string $table, int $ruleId): void
    {
        $deleted = $this->wpdb->delete($table, ['rule_id' => $ruleId], ['%d']);

        if (false === $deleted) {
            $this->queueDatabaseError(
                $ruleId,
                'child_delete',
                $table,
                sprintf('Failed to delete Pluginora child rows from "%s".', $table),
                ['rule_id' => $ruleId]
            );

            throw new RuntimeException(sprintf('Failed to delete Pluginora child rows from "%s".', $table));
        }
    }

    private function logDatabaseError(
        int $ruleId,
        string $contextType,
        string $contextReference,
        string $message,
        array $context = []
    ): void {
        $payload = wp_json_encode(
            [
                'message'  => $message,
                'db_error' => $this->wpdb->last_error,
                'context'  => $context,
            ]
        );

        if (! is_string($payload)) {
            $payload = $message;
        }

        $this->wpdb->insert(
            $this->tables->logs(),
            [
                'rule_id'           => max(0, $ruleId),
                'context_type'      => $contextType,
                'context_reference' => substr($contextReference, 0, 191),
                'message'           => $payload,
                'created_at_gmt'    => gmdate('Y-m-d H:i:s'),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }

    private function queueDatabaseError(
        int $ruleId,
        string $contextType,
        string $contextReference,
        string $message,
        array $context = []
    ): void {
        $this->pendingLogEntry = [
            'rule_id'           => $ruleId,
            'context_type'      => $contextType,
            'context_reference' => $contextReference,
            'message'           => $message,
            'context'           => $context,
        ];
    }

    private function flushQueuedDatabaseError(): void
    {
        if (! is_array($this->pendingLogEntry)) {
            return;
        }

        $entry = $this->pendingLogEntry;
        $this->pendingLogEntry = null;

        $this->logDatabaseError(
            (int) $entry['rule_id'],
            (string) $entry['context_type'],
            (string) $entry['context_reference'],
            (string) $entry['message'],
            is_array($entry['context']) ? $entry['context'] : []
        );
    }

    private function beginTransaction(): void
    {
        $this->pendingLogEntry = null;
        $this->wpdb->query('START TRANSACTION');
    }

    private function commitTransaction(): void
    {
        $this->wpdb->query('COMMIT');
        $this->pendingLogEntry = null;
    }

    private function rollbackTransaction(): void
    {
        $this->wpdb->query('ROLLBACK');
    }
}
