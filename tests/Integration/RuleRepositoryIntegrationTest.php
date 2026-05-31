<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Support\Rule;

final class RuleRepositoryIntegrationTest extends IntegrationTestCase
{
    public function test_save_and_find_persists_rule_children(): void
    {
        $rule = self::$rulePayloadMapper->fromPayload($this->makeSimpleDiscountPayload());

        $ruleId = self::$ruleRepository->save($rule);
        $stored = self::$ruleRepository->find($ruleId);

        self::assertIsInt($ruleId);
        self::assertInstanceOf(Rule::class, $stored);
        self::assertSame('Integration Discount', $stored->getName());
        self::assertSame('dynamic_pricing', $stored->getModule());
        self::assertSame('simple_discount', $stored->getRuleType());
        self::assertCount(1, $stored->getConditions());
        self::assertCount(5, $stored->getActions());
        self::assertCount(3, $stored->getItems());
    }

    public function test_duplicate_clones_rule_and_marks_copy_inactive(): void
    {
        $ruleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload($this->makeSimpleDiscountPayload())
        );
        $duplicateId = self::$ruleRepository->duplicate($ruleId);
        $duplicate   = self::$ruleRepository->find($duplicateId);

        self::assertNotSame($ruleId, $duplicateId);
        self::assertInstanceOf(Rule::class, $duplicate);
        self::assertSame('inactive', $duplicate->getStatus());
        self::assertSame('Integration Discount (Copy)', $duplicate->getName());
        self::assertCount(3, $duplicate->getItems());
    }

    public function test_update_status_and_query_filters_work(): void
    {
        $firstRuleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'name'   => 'Active Rule',
                        'status' => 'active',
                    ]
                )
            )
        );

        self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload(
                $this->makeSimpleDiscountPayload(
                    [
                        'name'   => 'Inactive Rule',
                        'status' => 'inactive',
                    ]
                )
            )
        );

        $activeRules = self::$ruleQueryRepository->findActiveByModule('dynamic_pricing');

        self::assertCount(1, $activeRules);
        self::assertSame('Active Rule', $activeRules[0]->getName());

        self::$ruleRepository->updateStatus($firstRuleId, 'inactive');

        $filteredRules = self::$ruleQueryRepository->findByFilters(
            [
                'module' => 'dynamic_pricing',
                'status' => 'inactive',
            ]
        );

        self::assertCount(2, $filteredRules);
    }

    public function test_update_status_failure_writes_log_entry(): void
    {
        global $wpdb;

        $rulesTable       = self::$tables->rules();
        $backupRulesTable = $rulesTable . '_backup';

        $wpdb->suppress_errors(true);
        $wpdb->query(sprintf('RENAME TABLE %s TO %s', $rulesTable, $backupRulesTable));
        $wpdb->suppress_errors(false);

        try {
            self::assertFalse(self::$ruleRepository->updateStatus(999, 'active'));
        } finally {
            $wpdb->suppress_errors(true);
            $wpdb->query(sprintf('RENAME TABLE %s TO %s', $backupRulesTable, $rulesTable));
            $wpdb->suppress_errors(false);
        }

        $logEntry = $wpdb->get_row(
            $wpdb->prepare(
                sprintf(
                    'SELECT * FROM %s WHERE context_type = %%s ORDER BY id DESC LIMIT 1',
                    self::$tables->logs()
                ),
                'rule_status_update'
            ),
            ARRAY_A
        );

        self::assertIsArray($logEntry);
        self::assertStringContainsString('Failed to update Pluginora rule status', (string) $logEntry['message']);
    }
}
