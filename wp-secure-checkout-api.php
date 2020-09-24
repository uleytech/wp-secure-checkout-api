<?php
/**
 * Plugin Name: Secure Checkout API
 * Version: 1.0.12
 * Plugin URI: https://github.com/uleytech/wp-secure-checkout-api
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Oleksandr Krokhin
 * Author URI: https://www.krohin.com
 * Description: Provides functionality for WordPress WooCommerce.
 * License: MIT
 */

require_once(__DIR__ . '/options.php');

/**
 * @param $data
 * @return array
 */
function getProducts($data)
{
    $items = [];
    foreach ($data as $itemId => $item) {
        $product = $item->get_product();
        $items[] = [
            'qty' => $item['quantity'],
            'uuid' => $product->get_sku(),
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
    $options = get_option('wp_secure_checkout_api_options');
    $apiToken = esc_attr($options['api_key']);
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
    $affId = $_COOKIE['aid'];
    $url = dirname(set_url_scheme('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']));
    $order = wc_get_order($order_id);

//    print_r($order->get_data());
//    print_r($order->get_items());
    $meta = array_values($order->get_meta('woocommerce_customized_payment_data'));

    $payment = $order->get_payment_method();
    $paymentData = [];
    switch ($payment) {
        case 'bacs':
        default:
            $paymentData['payment_method'] = 2;
            break;
        case 'custom_ad69e733ebae8d2':
            $paymentData['payment_method'] = 3;
              $paymentData['pay_pal_email'] = $meta[0][0]['PayPal Email'];
            break;
        case 'custom_e7f9e382dc50889':
            $paymentData['payment_method'] = 1;
            $cardExpiry = explode('/', $meta[0][1]['Card Expiry']);
            $paymentData['card_number'] = $meta[0][0]['Card Number'];
            $paymentData['card_expire_month'] = trim($cardExpiry[0]);
            $paymentData['card_expire_year'] = trim($cardExpiry[1]);
            $paymentData['card_cvv'] = $meta[0][2]['Card CVC'];
            break;
    }
    $productsData = [
        'products' => getProducts($order->get_items()),
    ];
    $billingData = [
        'payment_first_name' => $order->get_billing_first_name(),
        'payment_last_name' => $order->get_billing_last_name(),
        'payment_address' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
        'payment_city' => $order->get_billing_city(),
        'payment_country' => $order->get_billing_country(),
        'payment_postal_code' => $order->get_billing_postcode(),
    ];
    $shippingData = [
        'shipping_first_name' => $order->get_shipping_first_name(),
        'shipping_last_name' => $order->get_shipping_last_name(),
        'shipping_country' => $order->get_shipping_country(),
        'shipping_city' => $order->get_shipping_city(),
        'shipping_address' => $order->get_shipping_address_1() . ' ' .  $order->get_shipping_address_2(),
        'shipping_postal_code' => $order->get_shipping_postcode(),
        'shipping_insurance' => 0, // 1 -> shipping_cost += 5
        'shipping_cost' => 15,
    ];
    $mainData = [
        'telephone' => $order->get_billing_phone(),
        'email' => $order->get_billing_email(),
        'lang' => 'en',
        'ip_address' => $order->get_customer_ip_address(),
        'website' => $url,
        'sub_total' => $order->get_total(),
        'aff_id' => $affId,
        'currency' => $order->get_currency(),
        'coefficient' => 1,
    ];
    $data = array_merge($mainData, $billingData, $shippingData, $productsData, $paymentData);
    print_r($data);
    $sale = newSale($data);
    print_r($sale);
}

// add the action
//add_action('woocommerce_after_checkout_form', 'action_woocommerce_checkout_api', 10, 1);
add_action('woocommerce_thankyou', 'action_woocommerce_checkout_api', 10, 1);
