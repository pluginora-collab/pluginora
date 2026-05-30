<?php

declare(strict_types=1);

namespace Pluginora\Admin\Api;

use InvalidArgumentException;
use Pluginora\Admin\Forms\RulePayloadMapper;
use Pluginora\Admin\Forms\RuleSchemaProvider;
use Pluginora\Core\Contracts\HookableInterface;
use Pluginora\Repository\Contracts\RuleQueryRepositoryInterface;
use Pluginora\Repository\Contracts\RuleRepositoryInterface;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class RulesController implements HookableInterface
{
    public function __construct(
        private readonly RuleRepositoryInterface $ruleRepository,
        private readonly RuleQueryRepositoryInterface $ruleQueryRepository,
        private readonly RuleSchemaProvider $schemaProvider,
        private readonly RulePayloadMapper $payloadMapper
    ) {
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'pluginora/v1',
            '/builder/schema',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'schema'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                ],
            ]
        );

        register_rest_route(
            'pluginora/v1',
            '/rules',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'index'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'store'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                ],
            ]
        );

        register_rest_route(
            'pluginora/v1',
            '/rules/(?P<id>\d+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'show'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'update'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'destroy'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                ],
            ]
        );

        foreach (['duplicate', 'activate', 'deactivate'] as $action) {
            register_rest_route(
                'pluginora/v1',
                sprintf('/rules/(?P<id>\d+)/%s', $action),
                [
                    [
                        'methods'             => WP_REST_Server::CREATABLE,
                        'callback'            => [$this, $action],
                        'permission_callback' => [$this, 'permissionsCheck'],
                    ],
                ]
            );
        }
    }

    public function permissionsCheck(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    public function schema(): WP_REST_Response
    {
        return new WP_REST_Response($this->schemaProvider->getSchema());
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $filters = array_filter(
            [
                'module'    => sanitize_key((string) $request->get_param('module')),
                'status'    => sanitize_key((string) $request->get_param('status')),
                'rule_type' => sanitize_key((string) $request->get_param('rule_type')),
            ]
        );

        $rules = array_map(
            [$this->payloadMapper, 'toArray'],
            $this->ruleQueryRepository->findByFilters($filters)
        );

        return new WP_REST_Response(['items' => $rules]);
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $rule = $this->ruleRepository->find((int) $request['id']);

        if (null === $rule) {
            return new WP_Error(
                'pluginora_rule_not_found',
                __('Pluginora rule not found.', 'pluginora'),
                ['status' => 404]
            );
        }

        return new WP_REST_Response(['item' => $this->payloadMapper->toArray($rule)]);
    }

    public function store(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $rule   = $this->payloadMapper->fromPayload($this->getPayload($request));
            $ruleId = $this->ruleRepository->save($rule);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error(
                'pluginora_rule_validation_error',
                $exception->getMessage(),
                ['status' => 422]
            );
        } catch (Throwable $exception) {
            return new WP_Error(
                'pluginora_rule_create_failed',
                $exception->getMessage(),
                ['status' => 500]
            );
        }

        $stored = $this->ruleRepository->find($ruleId);

        return new WP_REST_Response(['item' => $stored ? $this->payloadMapper->toArray($stored) : null], 201);
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $existingRule = $this->ruleRepository->find((int) $request['id']);

        if (null === $existingRule) {
            return new WP_Error(
                'pluginora_rule_not_found',
                __('Pluginora rule not found.', 'pluginora'),
                ['status' => 404]
            );
        }

        try {
            $rule = $this->payloadMapper->fromPayload($this->getPayload($request), $existingRule);
            $this->ruleRepository->save($rule);
        } catch (InvalidArgumentException $exception) {
            return new WP_Error(
                'pluginora_rule_validation_error',
                $exception->getMessage(),
                ['status' => 422]
            );
        } catch (Throwable $exception) {
            return new WP_Error(
                'pluginora_rule_update_failed',
                $exception->getMessage(),
                ['status' => 500]
            );
        }

        $stored = $this->ruleRepository->find((int) $request['id']);

        return new WP_REST_Response(['item' => $stored ? $this->payloadMapper->toArray($stored) : null]);
    }

    public function destroy(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (null === $this->ruleRepository->find((int) $request['id'])) {
            return new WP_Error(
                'pluginora_rule_not_found',
                __('Pluginora rule not found.', 'pluginora'),
                ['status' => 404]
            );
        }

        if (! $this->ruleRepository->delete((int) $request['id'])) {
            return new WP_Error(
                'pluginora_rule_delete_failed',
                __('Failed to delete Pluginora rule.', 'pluginora'),
                ['status' => 500]
            );
        }

        return new WP_REST_Response(['deleted' => true]);
    }

    public function duplicate(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $ruleId = $this->ruleRepository->duplicate((int) $request['id']);
            $rule   = $this->ruleRepository->find($ruleId);
        } catch (Throwable $exception) {
            return new WP_Error('pluginora_rule_duplicate_failed', $exception->getMessage(), ['status' => 500]);
        }

        return new WP_REST_Response(['item' => $rule ? $this->payloadMapper->toArray($rule) : null], 201);
    }

    public function activate(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        return $this->updateStatus((int) $request['id'], 'active');
    }

    public function deactivate(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        return $this->updateStatus((int) $request['id'], 'inactive');
    }

    private function updateStatus(int $ruleId, string $status): WP_REST_Response|WP_Error
    {
        if (null === $this->ruleRepository->find($ruleId)) {
            return new WP_Error(
                'pluginora_rule_not_found',
                __('Pluginora rule not found.', 'pluginora'),
                ['status' => 404]
            );
        }

        if (! $this->ruleRepository->updateStatus($ruleId, $status)) {
            return new WP_Error(
                'pluginora_rule_status_failed',
                __('Failed to update Pluginora rule status.', 'pluginora'),
                ['status' => 500]
            );
        }

        $rule = $this->ruleRepository->find($ruleId);

        return new WP_REST_Response(['item' => $rule ? $this->payloadMapper->toArray($rule) : null]);
    }

    private function getPayload(WP_REST_Request $request): array
    {
        $payload = $request->get_json_params();

        return is_array($payload) ? $payload : [];
    }
}
