<?php

declare(strict_types=1);

if (! function_exists('wc_get_product_id_by_sku') || ! class_exists('WC_Product_Simple')) {
    fwrite(STDERR, "WooCommerce must be active before seeding Pluginora E2E fixtures.\n");
    exit(1);
}

$term = term_exists('pluginora-e2e', 'product_cat');

if (! $term) {
    $term = wp_insert_term(
        'Pluginora E2E',
        'product_cat',
        [
            'slug' => 'pluginora-e2e',
        ]
    );
}

if (is_wp_error($term) || empty($term['term_id'])) {
    fwrite(STDERR, "Unable to create the Pluginora E2E product category.\n");
    exit(1);
}

$categoryId = (int) $term['term_id'];

$productId = wc_get_product_id_by_sku('PLUGINORA-E2E-HOODIE');

if ($productId > 0) {
    $product = wc_get_product($productId);
} else {
    $product = new WC_Product_Simple();
}

if (! $product instanceof WC_Product_Simple) {
    $product = new WC_Product_Simple();
}

$product->set_name('Pluginora E2E Hoodie');
$product->set_slug('pluginora-e2e-hoodie');
$product->set_status('publish');
$product->set_catalog_visibility('visible');
$product->set_regular_price('120');
$product->set_price('120');
$product->set_sku('PLUGINORA-E2E-HOODIE');
$product->set_category_ids([$categoryId]);

$productId = $product->save();
$productUrl = get_permalink($productId);

if (! is_string($productUrl) || '' === $productUrl) {
    fwrite(STDERR, "Unable to determine the Pluginora E2E product URL.\n");
    exit(1);
}

echo 'PLUGINORA_E2E_BASE_URL=' . home_url() . PHP_EOL;
echo 'PLUGINORA_E2E_PRODUCT_ID=' . $productId . PHP_EOL;
echo 'PLUGINORA_E2E_PRODUCT_URL=' . $productUrl . PHP_EOL;
echo 'PLUGINORA_E2E_CART_URL=' . wc_get_cart_url() . PHP_EOL;
echo 'PLUGINORA_E2E_CHECKOUT_URL=' . wc_get_checkout_url() . PHP_EOL;
echo 'PLUGINORA_E2E_CATEGORY_ID=' . $categoryId . PHP_EOL;
