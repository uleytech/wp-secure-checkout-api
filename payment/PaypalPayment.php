<?php

if (!class_exists('WC_Payment_Gateway')) {
    return;
}
if (class_exists('PaypalPayment')) {
    return;
}

/**
 * Class PaypalPayment
 */
class PaypalPayment extends WC_Payment_Gateway
{
    /**
     * PaypalPayment constructor.
     */
    public function __construct()
    {
        $this->id = 'paypalpayment';
        $this->icon = plugin_dir_url(__FILE__) . '../img/paypal.svg';
        $this->method_title = __('Paypal Payment');
        $this->method_description = __('Internet acquiring and payment processing.');
        $this->has_fields = true;
        $this->supports = [
            'products'
        ];
        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        // Define user set variables
//        $this->token = $this->get_option('token');
//        $this->test_mode = $this->get_option('test_mode');
        //$this->stage_mode = $this->get_option('stage_mode');
        //$this->send_order = $this->get_option('send_order');
//        $this->tax_system = $this->get_option('tax_system');
//        $this->tax_type = $this->get_option('tax_type');
//        $this->success_url = $this->get_option('success_url');
//        $this->fail_url = $this->get_option('fail_url');
        //$this->ffd_version = $this->get_option('ffd_version');
        //$this->ffd_paymentMethodType = $this->get_option('ffd_paymentMethodType');
        //$this->ffd_paymentObjectType = $this->get_option('ffd_paymentObjectType');
//        $this->pData = get_plugin_data(__FILE__);
//        $this->mesurement_name = 'шт.';
        // Actions
//        add_action('valid-rbspayment-standard-ipn-reques', array($this, 'successful_request'));
//        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        // Save options
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [
            $this, 'process_admin_options'
        ]);
        // filters
//            add_filter('woocommerce_order_button_text', 'woo_custom_order_button_text');
//            function woo_custom_order_button_text()
//            {
//                return __('Перейти к оплате', 'woocommerce');
//            }
//        if (!$this->is_valid_for_use()) {
//            $this->enabled = false;
//        }
//        $this->callb();
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

    function payment_fields()
    {
        // ok, let's display some description before the payment form
        if ($this->description) {
            // display the description with <p> tags etc.
            echo wpautop(wp_kses_post($this->description));
        }
        // I will echo() the form, but you can close PHP tags and print it directly in HTML
        echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

        // Add this action hook if you want your custom payment gateway to support it
        do_action('woocommerce_credit_card_form_start', $this->id);

        // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
        echo '<div class="form-row form-row-wide"><label>Card Number <span class="required">*</span></label>
		<input id="misha_ccNo" type="text" autocomplete="off">
		</div>
		<div class="form-row form-row-first">
			<label>Expiry Date <span class="required">*</span></label>
			<input id="misha_expdate" type="text" autocomplete="off" placeholder="MM / YY">
		</div>
		<div class="form-row form-row-last">
			<label>Card Code (CVC) <span class="required">*</span></label>
			<input id="misha_cvv" type="password" autocomplete="off" placeholder="CVC">
		</div>
		<div class="clear"></div>';

        do_action('woocommerce_credit_card_form_end', $this->id);

        echo '<div class="clear"></div></fieldset>';
    }

    function validate_fields()
    {
        if( empty( $_POST[ 'billing_first_name' ]) ) {
            wc_add_notice(  'First name is required!', 'error' );
            return false;
        }
        return true;
    }
}
