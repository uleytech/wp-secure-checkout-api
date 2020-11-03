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
                'label' => __('Enable Paypal Payment', 'woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('The title that the user sees during the checkout process.',
                    'woocommerce'),
                'default' => __(SCA_PAYPAL_PAYMENT_TITLE, 'woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Description of the payment method that the client will see on your website.',
                    'woocommerce'),
                'default' => 'You can pay with your Paypal account',

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

        $scaPaypalEmail = filter_input( INPUT_POST, 'sca_paypal_email' );
        // Mark as on-hold (we're awaiting the cheque)
        $order->update_status('on-hold', __('Awaiting offline payment', 'woocommerce'));

        $order->update_meta_data('_sca_paypal_email', $scaPaypalEmail);
        $order->save();

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
//        echo '<fieldset id="wc-' . esc_attr($this->id) . '-paypal-form" class="wc-payment-form" style="background:transparent;">';

        // Add this action hook if you want your custom payment gateway to support it
//        do_action('woocommerce_credit_card_form_start', $this->id);

        // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
        echo '
            <div class="form-row validate-required form-row-wide">
                <label for="sca_paypal_email">Paypal Email 
                    <abbr class="required" title="обязательно">*</abbr>
                </label>
                <span class="woocommerce-input-wrapper">
                    <input id="sca_paypal_email" name="sca_paypal_email" type="email" autocomplete="off">
                </span>
            </div>
            <div class="clear"></div>
		';

//        do_action('woocommerce_credit_card_form_end', $this->id);

//        echo '<div class="clear"></div></fieldset>';
    }

    function validate_fields()
    {
        if (empty($_POST['sca_paypal_email'])
            || !preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/', $_POST['sca_paypal_email'])
        ) {
            wc_add_notice('Paypal Email is required!', 'error');
            return false;
        }
        return true;
    }
}
