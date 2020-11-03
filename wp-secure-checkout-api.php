<?php
/**
 * Plugin Name: Secure Checkout API
 * Version: 1.1.0
 * Plugin URI: https://github.com/uleytech/wp-secure-checkout-api
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Oleksandr Krokhin
 * Author URI: https://www.krohin.com
 * Description: Provides functionality for WordPress WooCommerce.
 * License: MIT
 */

require_once __DIR__ . '/include.php';
require_once __DIR__ . '/options.php';
require_once __DIR__ . '/update.php';
require_once __DIR__ . '/payment/BankWirePayment.php';

if (is_admin()) {
    new ScaUpdater(
        __FILE__,
        'uleytech',
        "wp-secure-checkout-api"
    );
}

function scaSettingsLink($links)
{
    $url = esc_url(add_query_arg(
        'page',
        'wc-product-manager-api',
        get_admin_url() . 'options-general.php'
    ));
    $link[] = "<a href='$url'>" . __('Settings') . '</a>';


    return array_merge($link, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'scaSettingsLink');

function scaPaymentLink($links, $file)
{
    $base = plugin_basename(__FILE__);
    if ($file == $base) {
        $url = 'admin.php?page=wc-settings&tab=checkout&section=bankwirepayment';
        $links[] = "<a href='$url'>" . __('BankWire') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'scaPaymentLink', 10, 2);

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
    $ch = curl_init();
    $token = [
        'token' => esc_attr($options['api_key'] ?? ''),
    ];
    $data = array_merge($data, $token);
    $parameters = http_build_query($data);
    curl_setopt($ch, CURLOPT_URL, SCA_API_URL . '/sale');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function action_woocommerce_checkout_api($order_id)
{
    if (!$order_id) {
        return;
    }

    $affId = $_COOKIE['aid'];
    $url = dirname(set_url_scheme('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']));
    $order = wc_get_order($order_id);

    $orderNumber = $order->get_meta('_new_order_number');
    if(!empty($orderNumber)) {
        return;
    }

    $options = get_option('wp_secure_checkout_api_options');

    $meta = array_values($order->get_meta('woocommerce_customized_payment_data'));

//    $payment = $order->get_payment_method_title();
    $payment = $order->get_payment_method();
    $paymentData = [];
    switch ($payment) {
//        case 'Payment via BankWire':
        case 'bankwirepayment':
//        case $options['payment_method_bank']:
        default:
            $paymentData['payment_method'] = 2;
            break;
        case 'Payment via PayPal':
//        case $options['payment_method_paypal']:
            $paymentData['payment_method'] = 3;
            $paymentData['pay_pal_email'] = $meta[0][0][$options['paypal_email']];
            break;
        case 'Payment via Credit Card':
//        case $options['payment_method_card']:
            $paymentData['payment_method'] = 1;
            $cardExpiry = explode('/', $meta[0][1][$options['card_expiry']]);
            $paymentData['card_number'] = $meta[0][0][$options['card_number']];
            $paymentData['card_expire_month'] = trim($cardExpiry[0]);
            $paymentData['card_expire_year'] = trim($cardExpiry[1]);
            $paymentData['card_cvv'] = $meta[0][2][$options['card_cvv']];
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
        'shipping_address' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
        'shipping_postal_code' => $order->get_shipping_postcode(),
        'shipping_insurance' => 0, // 1 -> shipping_cost += 5
        'shipping_cost' => $order->get_shipping_total(), // > 200 ? 0 : 15,
    ];
    $mainData = [
        'telephone' => $order->get_billing_phone(),
        'email' => $order->get_billing_email(),
        'lang' => 'en',
        'ip_address' => $order->get_customer_ip_address(),
        'website' => $url,
        'sub_total' => $order->get_subtotal(),
        'aff_id' => $affId,
        'currency' => $order->get_currency(),
        'coefficient' => 1,
    ];
    $data = array_merge($mainData, $billingData, $shippingData, $productsData, $paymentData);
    $sale = newSale($data);
    $saleOrder = json_decode($sale, true);
    if (is_array($saleOrder) && array_key_exists('order_id', $saleOrder)) {
        $order->update_meta_data('_new_order_number', $saleOrder['order_id']);
        $order->save();
    }
// 	wp_redirect(get_permalink());
    wp_redirect($_SERVER['REQUEST_URI']);
}
// add_action('woocommerce_checkout_order_processed', 'action_woocommerce_checkout_api', 10, 1);
// add_action('woocommerce_checkout_order_created', 'action_woocommerce_checkout_api', 10, 1);
add_action('woocommerce_thankyou', 'action_woocommerce_checkout_api', 1, 1);

function filter_woocommerce_order_number($default_order_number, \WC_Order $order)
{
    //Load in our meta value. Return it, if it's not empty.
    $order_number = $order->get_meta('_new_order_number');

    if (!empty($order_number)) {
        return $order_number;
    }
    // use whatever the previous value was, if a plugin modified it already.
    return $default_order_number;
}
add_filter('woocommerce_order_number', 'filter_woocommerce_order_number', 10, 2);

function action_woocommerce_thankyou($order_id) {
    $order = wc_get_order($order_id);
    $order_number = $order->get_meta('_new_order_number');
//    if (!empty($order_number)) {
//        $order->update_status('processing');
//        $order->save();
//    }
}
add_action('woocommerce_thankyou', 'action_woocommerce_thankyou', 10, 1);

function addBankWirePayment($methods)
{
    $methods[] = 'BankWirePayment';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'addBankWirePayment');