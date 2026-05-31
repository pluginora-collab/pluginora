<?php

declare(strict_types=1);

namespace Pluginora\Tests\Integration;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class RulesControllerIntegrationTest extends IntegrationTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        self::$rulesController->register();
        do_action('rest_api_init');
    }

    public function test_store_and_show_round_trip_rule_payload(): void
    {
        $storeRequest = new WP_REST_Request('POST', '/pluginora/v1/rules');
        $storeRequest->set_body(wp_json_encode($this->makeSimpleDiscountPayload()));
        $storeRequest->set_header('content-type', 'application/json');

        $storeResponse = self::$rulesController->store($storeRequest);

        self::assertInstanceOf(WP_REST_Response::class, $storeResponse);
        self::assertSame(201, $storeResponse->get_status());

        $storedItem = $storeResponse->get_data()['item'];

        self::assertSame('Integration Discount', $storedItem['name']);
        self::assertSame('dynamic_pricing', $storedItem['module']);
        self::assertSame([101, 202], $storedItem['selected_products']);

        $showRequest = new WP_REST_Request('GET', '/pluginora/v1/rules/' . $storedItem['id']);
        $showRequest->set_url_params(['id' => (string) $storedItem['id']]);

        $showResponse = self::$rulesController->show($showRequest);

        self::assertInstanceOf(WP_REST_Response::class, $showResponse);
        self::assertSame($storedItem['id'], $showResponse->get_data()['item']['id']);
    }

    public function test_update_duplicate_activate_and_delete_work(): void
    {
        $ruleId = self::$ruleRepository->save(
            self::$rulePayloadMapper->fromPayload($this->makeSimpleDiscountPayload())
        );

        $updateRequest = new WP_REST_Request('POST', '/pluginora/v1/rules/' . $ruleId);
        $updateRequest->set_url_params(['id' => (string) $ruleId]);
        $updateRequest->set_body(
            wp_json_encode(
                $this->makeSimpleDiscountPayload(
                    [
                        'name'           => 'Updated Integration Discount',
                        'discount_value' => 25,
                    ]
                )
            )
        );
        $updateRequest->set_header('content-type', 'application/json');

        $updateResponse = self::$rulesController->update($updateRequest);

        self::assertInstanceOf(WP_REST_Response::class, $updateResponse);
        self::assertSame('Updated Integration Discount', $updateResponse->get_data()['item']['name']);
        self::assertSame(25.0, (float) $updateResponse->get_data()['item']['discount_value']);

        $duplicateRequest = new WP_REST_Request('POST', '/pluginora/v1/rules/' . $ruleId . '/duplicate');
        $duplicateRequest->set_url_params(['id' => (string) $ruleId]);

        $duplicateResponse = self::$rulesController->duplicate($duplicateRequest);

        self::assertInstanceOf(WP_REST_Response::class, $duplicateResponse);
        self::assertStringContainsString('(Copy)', $duplicateResponse->get_data()['item']['name']);

        $activateRequest = new WP_REST_Request('POST', '/pluginora/v1/rules/' . $ruleId . '/activate');
        $activateRequest->set_url_params(['id' => (string) $ruleId]);

        $activateResponse = self::$rulesController->activate($activateRequest);

        self::assertInstanceOf(WP_REST_Response::class, $activateResponse);
        self::assertSame('active', $activateResponse->get_data()['item']['status']);

        $deleteRequest = new WP_REST_Request('DELETE', '/pluginora/v1/rules/' . $ruleId);
        $deleteRequest->set_url_params(['id' => (string) $ruleId]);

        $deleteResponse = self::$rulesController->destroy($deleteRequest);

        self::assertInstanceOf(WP_REST_Response::class, $deleteResponse);
        self::assertTrue($deleteResponse->get_data()['deleted']);
        self::assertNull(self::$ruleRepository->find($ruleId));
    }

    public function test_store_rejects_invalid_payload(): void
    {
        $request = new WP_REST_Request('POST', '/pluginora/v1/rules');
        $request->set_body(
            wp_json_encode(
                [
                    'module'    => 'dynamic_pricing',
                    'rule_type' => 'simple_discount',
                    'name'      => '',
                ]
            )
        );
        $request->set_header('content-type', 'application/json');

        $response = self::$rulesController->store($request);

        self::assertInstanceOf(WP_Error::class, $response);
        self::assertSame('pluginora_rule_validation_error', $response->get_error_code());
    }

    public function test_registered_route_rejects_invalid_module_before_callback(): void
    {
        $request = new WP_REST_Request('POST', '/pluginora/v1/rules');
        $request->set_body(
            wp_json_encode(
                [
                    'module'    => 'invalid_module',
                    'rule_type' => 'simple_discount',
                    'name'      => 'Invalid Rule',
                ]
            )
        );
        $request->set_header('content-type', 'application/json');

        $response = rest_do_request($request);

        self::assertSame(400, $response->get_status());
        self::assertSame('rest_invalid_param', $response->get_data()['code']);
    }
}
