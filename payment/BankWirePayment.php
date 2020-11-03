<?php

if (!class_exists('WC_Payment_Gateway')) {
    return;
}
if (class_exists('BankWirePayment')) {
    return;
}

/**
 * Class BankWirePayment
 */
class BankWirePayment extends WC_Payment_Gateway
{
    /**
     * BankWirePayment constructor.
     */
    public function __construct()
    {
        $this->id = 'bankwirepayment';
        $this->icon = plugin_dir_url(__FILE__) . '../img/bankwire.svg';
        $this->method_title = __('BankWire Payment');
        $this->method_description = __('Internet acquiring and payment processing.');
        $this->has_fields = false;
        $this->supports = [
            'products'
        ];
        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        // Save options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [
            $this, 'process_admin_options'
        ]);
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable BankWire Payment', 'woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('The title that the user sees during the checkout process.',
                    'woocommerce'),
                'default' => __(SCA_BANKWIRE_PAYMENT_TITLE, 'woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Description of the payment method that the client will see on your website.',
                    'woocommerce'),
                'default' => 'You can pay with your bank account',

            ),
        );
    }

    /**
     * @param int $order_id
     * @return array
     */
    function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);

        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status('on-hold', __('Awaiting offline payment', 'woocommerce'));

        // Remove cart
        $woocommerce->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
}
