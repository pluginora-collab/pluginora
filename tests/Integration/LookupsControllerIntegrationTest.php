<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use Pluginora\Admin\Api\LookupsController;
use WC_Product_Simple;
use WP_REST_Request;
use WP_REST_Response;

final class LookupsControllerIntegrationTest extends IntegrationTestCase
{
    private LookupsController $controller;

    public function set_up(): void
    {
        parent::set_up();

        if (! class_exists('WC_Product_Simple')) {
            self::markTestSkipped('WooCommerce product APIs are required for lookup controller integration tests.');
        }

        $this->controller = new LookupsController();
        $this->controller->register();
        do_action('rest_api_init');
    }

    public function test_permissions_check_allows_administrator_with_manage_woocommerce(): void
    {
        self::assertTrue($this->controller->permissionsCheck());
    }

    public function test_products_returns_requested_product_ids(): void
    {
        $firstProduct  = $this->createProduct('Lookup Product Alpha');
        $secondProduct = $this->createProduct('Lookup Product Beta');

        $request = new WP_REST_Request('GET', '/pluginora/v1/lookups/products');
        $request->set_param('include', $firstProduct->get_id() . ',' . $secondProduct->get_id());

        $response = $this->controller->products($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(
            [
                [
                    'id'    => $firstProduct->get_id(),
                    'label' => 'Lookup Product Alpha',
                ],
                [
                    'id'    => $secondProduct->get_id(),
                    'label' => 'Lookup Product Beta',
                ],
            ],
            $response->get_data()['items']
        );
    }

    public function test_categories_returns_requested_terms(): void
    {
        $firstTerm  = wp_insert_term('Lookup Category One', 'product_cat');
        $secondTerm = wp_insert_term('Lookup Category Two', 'product_cat');

        $request = new WP_REST_Request('GET', '/pluginora/v1/lookups/categories');
        $request->set_param('include', [(int) $firstTerm['term_id'], (int) $secondTerm['term_id']]);

        $response = $this->controller->categories($request);

        self::assertInstanceOf(WP_REST_Response::class, $response);
        self::assertSame(
            [
                [
                    'id'    => (int) $firstTerm['term_id'],
                    'label' => 'Lookup Category One',
                ],
                [
                    'id'    => (int) $secondTerm['term_id'],
                    'label' => 'Lookup Category Two',
                ],
            ],
            $response->get_data()['items']
        );
    }

    public function test_registered_route_rejects_invalid_include_parameter(): void
    {
        $request = new WP_REST_Request('GET', '/pluginora/v1/lookups/products');
        $request->set_param('include', 'abc');

        $response = rest_do_request($request);

        self::assertSame(400, $response->get_status());
        self::assertSame('rest_invalid_param', $response->get_data()['code']);
    }

    private function createProduct(string $name): WC_Product_Simple
    {
        $product = new WC_Product_Simple();
        $product->set_name($name);
        $product->set_status('publish');
        $product->set_regular_price('25');
        $product->save();

        return $product;
    }
}
