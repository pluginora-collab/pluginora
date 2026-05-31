<?php

declare(strict_types=1);

namespace Pluginora\Admin\Api;

use Pluginora\Core\Contracts\HookableInterface;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class LookupsController implements HookableInterface
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'pluginora/v1',
            '/lookups/products',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'products'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                ],
            ]
        );

        register_rest_route(
            'pluginora/v1',
            '/lookups/categories',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'categories'],
                    'permission_callback' => [$this, 'permissionsCheck'],
                ],
            ]
        );
    }

    public function permissionsCheck(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    public function products(WP_REST_Request $request): WP_REST_Response
    {
        $search = sanitize_text_field((string) $request->get_param('search'));
        $ids    = $this->parseIds($request->get_param('include'));
        $query  = [
            'limit'  => 20,
            'return' => 'ids',
            'status' => ['publish', 'private'],
        ];

        if ([] !== $ids) {
            $query['include'] = $ids;
            $query['limit']   = count($ids);
        } elseif ('' !== $search) {
            $query['search'] = '*' . $search . '*';
        }

        $productIds = function_exists('wc_get_products') ? wc_get_products($query) : [];
        $productIds = $this->sortIntegersByRequestedOrder($productIds, $ids);
        $items      = [];

        foreach ($productIds as $productId) {
            $items[] = [
                'id'    => (int) $productId,
                'label' => html_entity_decode(get_the_title((int) $productId), ENT_QUOTES, get_bloginfo('charset')),
            ];
        }

        return new WP_REST_Response(['items' => $items]);
    }

    public function categories(WP_REST_Request $request): WP_REST_Response
    {
        $search = sanitize_text_field((string) $request->get_param('search'));
        $ids    = $this->parseIds($request->get_param('include'));
        $args   = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'number'     => 20,
        ];

        if ([] !== $ids) {
            $args['include'] = $ids;
            $args['number']  = count($ids);
        } elseif ('' !== $search) {
            $args['search'] = $search;
        }

        $terms = get_terms($args);
        $terms = $this->sortTermsByRequestedOrder($terms, $ids);
        $items = [];

        if (! is_wp_error($terms)) {
            foreach ($terms as $term) {
                $items[] = [
                    'id'    => (int) $term->term_id,
                    'label' => $term->name,
                ];
            }
        }

        return new WP_REST_Response(['items' => $items]);
    }

    private function parseIds(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('absint', $value)));
        }

        if (is_string($value) && '' !== $value) {
            return array_values(array_filter(array_map('absint', explode(',', $value))));
        }

        return [];
    }

    private function sortIntegersByRequestedOrder(array $values, array $requestedIds): array
    {
        if ([] === $requestedIds) {
            return $values;
        }

        $requestedOrder = array_flip($requestedIds);

        usort(
            $values,
            static fn (mixed $left, mixed $right): int =>
                ($requestedOrder[(int) $left] ?? PHP_INT_MAX) <=> ($requestedOrder[(int) $right] ?? PHP_INT_MAX)
        );

        return $values;
    }

    private function sortTermsByRequestedOrder(mixed $terms, array $requestedIds): mixed
    {
        if ([] === $requestedIds || ! is_array($terms)) {
            return $terms;
        }

        $requestedOrder = array_flip($requestedIds);

        usort(
            $terms,
            static fn (object $left, object $right): int =>
                ($requestedOrder[(int) $left->term_id] ?? PHP_INT_MAX)
                <=> ($requestedOrder[(int) $right->term_id] ?? PHP_INT_MAX)
        );

        return $terms;
    }
}
