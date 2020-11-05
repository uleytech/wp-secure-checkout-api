<?php

if (!class_exists('WC_Payment_Gateway')) {
    return;
}
if (class_exists('CreditCardPayment')) {
    return;
}

/**
 * Class PaypalPayment
 */
class CreditCardPayment extends WC_Payment_Gateway
{
    /**
     * PaypalPayment constructor.
     */
    public function __construct()
    {
        $this->id = 'creditcardpayment';
        $this->icon = plugin_dir_url(__FILE__) . '../img/creditcard.svg';
        $this->method_title = __('Credit Card Payment');
        $this->method_description = __('Internet acquiring and payment processing.');
        $this->has_fields = true;
        $this->supports = [
            'products'
        ];
//        $this->supports = array( 'default_credit_card_form' );

        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

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
                'label' => __('Enable CreditCard Payment', 'woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('The title that the user sees during the checkout process.',
                    'woocommerce'),
                'default' => __(SCA_CREDITCARD_PAYMENT_TITLE, 'woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('Description of the payment method that the client will see on your website.',
                    'woocommerce'),
                'default' => 'You can pay with your credit / debit card',

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
        $scaCardNumber = filter_input(INPUT_POST, 'sca_card_number');
        $scaExpDate = filter_input(INPUT_POST, 'sca_expdate');
        $scaCvv = filter_input(INPUT_POST, 'sca_cvv');

        $order->update_meta_data('_sca_card_number', $scaCardNumber);
        $order->update_meta_data('_sca_expdate', $scaExpDate);
        $order->update_meta_data('_sca_cvv', $scaCvv);
        $order->save();

        do_action('action_sca_create_sale_order', $order);

        $orderNumber = $order->get_meta('_new_order_number');
        if ($orderNumber) {
            $order->update_status('on-hold', __('Awaiting offline payment', 'woocommerce'));
        } else {
            $order->update_status('failed', __('Failed payment', 'woocommerce'));
        }

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
        wp_enqueue_script('wc-credit-card-form');
        // Add this action hook if you want your custom payment gateway to support it
//        do_action('woocommerce_credit_card_form_start', $this->id);

        // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
//        echo $this->credit_card_form();
        echo '
        <div class="form-row validate-required form-row-wide">
            <label for="sca_card_number">Card Number 
                <abbr class="required" title="required">*</abbr>
            </label>
            <span class="woocommerce-input-wrapper">
		        <input id="sca_card_number" name="sca_card_number" maxlength="19" class="input-text wc-credit-card-form-card-number" 
		        inputmode="numeric" type="tel" autocomplete="cc-number" autocorrect="no" 
		        placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;">
		    </span>
		</div>
		<div class="form-row validate-required form-row-first">
			<label for="sca_expdate">Expiry Date 
			    <abbr class="required" title="required">*</abbr>    
			</label>
			<span class="woocommerce-input-wrapper">
			    <input id="sca_expdate" name="sca_expdate" class="input-text wc-credit-card-form-card-expiry" 
			    inputmode="numeric" autocorrect="no" type="tel" autocomplete="no" 
			    placeholder="' . esc_attr__('MM / YYYY', 'woocommerce') . '">
			</span>
		</div>
		<div class="form-row validate-required form-row-last">
			<label for="sca_cvv">Code (CVC)
			    <abbr class="required" title="required">*</abbr>    
			</label>
			<span class="woocommerce-input-wrapper">
			    <input id="sca_cvv" name="sca_cvv" type="password" maxlength="4" autocomplete="cc-exp" 
			    inputmode="numeric" class="input-text wc-credit-card-form-card-cvc" 
			    placeholder="' . esc_attr__('CVC', 'woocommerce') . '">
			</span>
		</div>
		<div class="clear"></div>';
//        do_action('woocommerce_credit_card_form_end', $this->id);
    }

    function validate_fields()
    {
        $errors = false;
        if (empty($_POST['sca_card_number'])
            || !preg_match('/^[0-9 ]{16,19}$/', $_POST['sca_card_number'])
        ) {
            wc_add_notice('Card Number is required!', 'error');
            $errors = true;
        }
        if (empty($_POST['sca_expdate'])
            || !preg_match('/^[0-9]{2} ?\/ ?[0-9]{4}$/', $_POST['sca_expdate'])
        ) {
            wc_add_notice('Expiry Date is required!', 'error');
            $errors = true;
        }
        if (isset($_POST['sca_expdate'])) {
            $scaExpDate = explode('/', $_POST['sca_expdate']);
            if ((int)$scaExpDate[0] < 1
                || (int)$scaExpDate[0] > 12
                || (int)$scaExpDate[0] < (int)date('m')
            )  {
                wc_add_notice('Expiry Month incorrect!', 'error');
                $errors = true;
            }
            if ((int)$scaExpDate[1] < (int)date('Y')) {
                wc_add_notice('Expiry Year incorrect!', 'error');
                $errors = true;
            }
        }
        if (empty($_POST['sca_cvv'])
            || !preg_match('/^[0-9]{3,4}$/', $_POST['sca_cvv'])
        ) {
            wc_add_notice('Code (CVC) is required!', 'error');
            $errors = true;
        }
        return $errors ? true : false;
    }
}
