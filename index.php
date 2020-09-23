<?php
/*
Plugin Name: Secure Checkout API
Version: 1.0.0.
Description: Provides functionality for WordPress theme.
License: MIT
*/

//if (file_exists($filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . '.' . basename(dirname(__FILE__)) . '.php')
//    && !class_exists('WPTemplatesOptions')
//) {
//    include_once($filename);
//}

/**
 * @param $data
 * @return array
 */
function getProducts($data)
{
    $items = [];
    foreach ($data as $itemId => $item) {
//        $itemData = $item->get_data();
        $product = $item->get_product();
//        $qty = $product->get_quantity();
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
    $apiToken = 'tvETxRjptC6PkuWE';
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

    print_r($order->get_data());
    print_r($order->get_items());

    $payment = $order->get_payment_method();
    $paymentData = [];
    switch ($payment) {
        case 'bacs':
        default:
            $paymentData['payment_method'] = 2;
            break;
//        case 'PayPal':
//            $paymentData['payment_method'] = 1;
//              $paymentData['pay_pal_email'] => '1@1.cc',
//            break;
//        case 'CreditCard':
//              $paymentData['payment_method'] = 3;
//              $paymentData['card_number'] => '41111',
//              $paymentData['card_expire_month'] => '05',
//              $paymentData['card_expire_year'] => '2020',
//              $paymentData['card_cvv'] => '332',
//            break;
    }
//    $orderData = $order->get_data();
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

//Object ( [refunded_line_items:protected] => [status_transition:protected] => [data:protected] => Array (
// [parent_id] => 0
// [status] => on-hold
// [currency] => EUR
// [version] => 4.3.3
// [prices_include_tax] =>
// [date_created] => WC_DateTime Object ( [utc_offset:protected] => 0 [date] => 2020-09-22 10:45:49.000000 [timezone_type] => 1 [timezone] => +00:00 )
// [date_modified] => WC_DateTime Object ( [utc_offset:protected] => 0 [date] => 2020-09-22 10:45:49.000000 [timezone_type] => 1 [timezone] => +00:00 )
// [discount_total] => 0
// [discount_tax] => 0
// [shipping_total] => 0.00
// [shipping_tax] => 0
// [cart_tax] => 0
// [total] => 90.00
// [total_tax] => 0
// [customer_id] => 2
// [order_key] => wc_order_b9y97d9Z6yRQ9
// [billing] => Array ( [first_name] => Axel [last_name] => Kaa [company] => [address_1] => aaaaaaa [address_2] => [city] => Ccccc [state] => [postcode] => 11111 [country] => DE [email] => bogdan@email.com [phone] => 111111111 )
// [shipping] => Array ( [first_name] => [last_name] => [company] => [address_1] => [address_2] => [city] => [state] => [postcode] => [country] => )
// [payment_method] => bacs
// [payment_method_title] => Direct bank transfer
// [transaction_id] =>
// [customer_ip_address] => 91.238.23.32
// [customer_user_agent] => Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.2 Safari/605.1.15
// [created_via] => checkout
// [customer_note] =>
// [date_completed] =>
// [date_paid] =>
// [cart_hash] => 3f33fd7fbc98f8ab9a6853a8d5a8d63d )
// [items:protected] => Array ( )
// [items_to_delete:protected] => Array ( )
// [cache_group:protected] => orders
// [data_store_name:protected] => order
// [object_type:protected] => order [id:protected] => 6520 [changes:protected] => Array ( ) [object_read:protected] => 1 [extra_data:protected] => Array ( ) [default_data:protected] => Array ( [parent_id] => 0 [status] => [currency] => [version] => [prices_include_tax] => [date_created] => [date_modified] => [discount_total] => 0 [discount_tax] => 0 [shipping_total] => 0 [shipping_tax] => 0 [cart_tax] => 0 [total] => 0 [total_tax] => 0 [customer_id] => 0 [order_key] => [billing] => Array ( [first_name] => [last_name] => [company] => [address_1] => [address_2] => [city] => [state] => [postcode] => [country] => [email] => [phone] => ) [shipping] => Array ( [first_name] => [last_name] => [company] => [address_1] => [address_2] => [city] => [state] => [postcode] => [country] => ) [payment_method] => [payment_method_title] => [transaction_id] => [customer_ip_address] => [customer_user_agent] => [created_via] => [customer_note] => [date_completed] => [date_paid] => [cart_hash] => )
// [data_store:protected] => WC_Data_Store Object ( [instance:WC_Data_Store:private] => WC_Order_Data_Store_CPT Object ( [internal_meta_keys:protected] => Array ( [0] => _customer_user [1] => _order_key [2] => _order_currency [3] => _billing_first_name [4] => _billing_last_name [5] => _billing_company [6] => _billing_address_1 [7] => _billing_address_2 [8] => _billing_city [9] => _billing_state [10] => _billing_postcode [11] => _billing_country [12] => _billing_email [13] => _billing_phone [14] => _shipping_first_name [15] => _shipping_last_name [16] => _shipping_company [17] => _shipping_address_1 [18] => _shipping_address_2 [19] => _shipping_city [20] => _shipping_state [21] => _shipping_postcode [22] => _shipping_country [23] => _completed_date [24] => _paid_date [25] => _edit_lock [26] => _edit_last [27] => _cart_discount [28] => _cart_discount_tax [29] => _order_shipping [30] => _order_shipping_tax [31] => _order_tax [32] => _order_total [33] => _payment_method [34] => _payment_method_title [35] => _transaction_id [36] => _customer_ip_address [37] => _customer_user_agent [38] => _created_via [39] => _order_version [40] => _prices_include_tax [41] => _date_completed [42] => _date_paid [43] => _payment_tokens [44] => _billing_address_index [45] => _shipping_address_index [46] => _recorded_sales [47] => _recorded_coupon_usage_counts [48] => _download_permissions_granted [49] => _order_stock_reduced ) [meta_type:protected] => post [object_id_field_for_meta:protected] =>
// [must_exist_meta_keys:protected] => Array ( ) ) [stores:WC_Data_Store:private] => Array ( [coupon] => WC_Coupon_Data_Store_CPT [customer] => WC_Customer_Data_Store [customer-download] => WC_Customer_Download_Data_Store [customer-download-log] => WC_Customer_Download_Log_Data_Store [customer-session] => WC_Customer_Data_Store_Session [order] => WC_Order_Data_Store_CPT [order-refund] => WC_Order_Refund_Data_Store_CPT [order-item] => WC_Order_Item_Data_Store [order-item-coupon] => WC_Order_Item_Coupon_Data_Store [order-item-fee] => WC_Order_Item_Fee_Data_Store [order-item-product] => WC_Order_Item_Product_Data_Store [order-item-shipping] => WC_Order_Item_Shipping_Data_Store [order-item-tax] => WC_Order_Item_Tax_Data_Store [payment-token] => WC_Payment_Token_Data_Store [product] => WC_Product_Data_Store_CPT [product-grouped] => WC_Product_Grouped_Data_Store_CPT [product-variable] => WC_Product_Variable_Data_Store_CPT [product-variation] => WC_Product_Variation_Data_Store_CPT [shipping-zone] => WC_Shipping_Zone_Data_Store [webhook] => WC_Webhook_Data_Store [report-revenue-stats] => Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore
// [report-orders] => Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore
// [report-orders-stats] => Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore
// [report-products] => Automattic\WooCommerce\Admin\API\Reports\Products\DataStore [report-variations] => Automattic\WooCommerce\Admin\API\Reports\Variations\DataStore [report-products-stats] => Automattic\WooCommerce\Admin\API\Reports\Products\Stats\DataStore [report-categories] => Automattic\WooCommerce\Admin\API\Reports\Categories\DataStore [report-taxes] => Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore [report-taxes-stats] => Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore [report-coupons] => Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore [report-coupons-stats] => Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats\DataStore [report-downloads] => Automattic\WooCommerce\Admin\API\Reports\Downloads\DataStore [report-downloads-stats] => Automattic\WooCommerce\Admin\API\Reports\Downloads\Stats\DataStore [admin-note] => Automattic\WooCommerce\Admin\Notes\DataStore [report-customers] => Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore [report-customers-stats] => Automattic\WooCommerce\Admin\API\Reports\Customers\Stats\DataStore [report-stock-stats] => Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\DataStore ) [current_class_name:WC_Data_Store:private] => WC_Order_Data_Store_CPT [object_type:WC_Data_Store:private] => order )
// [meta_data:protected] => Array ( [0] => WC_Meta_Data Object ( [current_data:protected] => Array ( [id] => 173289 [key] => is_vat_exempt [value] => no ) [data:protected] => Array ( [id] => 173289 [key] => is_vat_exempt [value] => no ) ) ) )
//
// Array ( [payment_method] => 2
// [payment_first_name] => billing_first_name
// [payment_last_name] => billing_last_name
// [payment_address] => billing_address_1
// [payment_city] => billing_city
// [payment_country] => billing_city
// [payment_postal_code] => billing_postcode
// [shipping_first_name] => shipping_first_name
// [shipping_last_name] => shipping_last_name [shipping_country] => shipping_city
// [shipping_city] => shipping_city [shipping_address] => shipping_address_1
// [shipping_postal_code] => shipping_postcode [shipping_insurance] => 0
// [products] => Array ( )
// [telephone] => [email] => billing_email
// [lang] => en
// [ip_address] => 91.238.23.32
// [website] => https://medrx24.biz
// [sub_total] => 40
// [shipping_cost] => 15
// [aff_id] => 71031 )