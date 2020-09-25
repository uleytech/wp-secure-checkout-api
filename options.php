<?php

function sca_add_settings_page()
{
    add_options_page('Secure Checkout API', 'Secure Checkout API', 'manage_options', 'wp-secure-checkout-api', 'sca_render_plugin_settings_page');
}

add_action('admin_menu', 'sca_add_settings_page');

function sca_render_plugin_settings_page()
{
    ?>
    <h2>Secure Checkout API Settings</h2>
    <form action="options.php" method="post">
        <?php
        settings_fields('wp_secure_checkout_api_options');
        do_settings_sections('wp_secure_checkout_api'); ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>"/>
    </form>
    <?php
}

function sca_register_settings()
{
    register_setting('wp_secure_checkout_api_options', 'wp_secure_checkout_api_options', 'wp_secure_checkout_api_options_validate');
    add_settings_section('api_settings', 'API Settings', 'wp_secure_checkout_api_section_text', 'wp_secure_checkout_api');
    add_settings_field('wp_secure_checkout_api_setting_api_key', 'API Key', 'wp_secure_checkout_api_setting_api_key', 'wp_secure_checkout_api', 'api_settings');
    add_settings_field('wp_secure_checkout_api_setting_payment_method_bank', 'Bank wire Title', 'wp_secure_checkout_api_setting_payment_method_bank', 'wp_secure_checkout_api', 'api_settings');
    add_settings_field('wp_secure_checkout_api_setting_payment_method_paypal', 'Paypal Title', 'wp_secure_checkout_api_setting_payment_method_paypal', 'wp_secure_checkout_api', 'api_settings');
    add_settings_field('wp_secure_checkout_api_setting_paypal_email', 'Paypal Email', 'wp_secure_checkout_api_setting_paypal_email', 'wp_secure_checkout_api', 'api_settings');
    add_settings_field('wp_secure_checkout_api_setting_payment_method_card', 'Card Title', 'wp_secure_checkout_api_setting_payment_method_card', 'wp_secure_checkout_api', 'api_settings');
    add_settings_field('wp_secure_checkout_api_setting_card_number', 'Card Number', 'wp_secure_checkout_api_setting_card_number', 'wp_secure_checkout_api', 'api_settings');
    add_settings_field('wp_secure_checkout_api_setting_card_expiry', 'Card Expiry', 'wp_secure_checkout_api_setting_card_expiry', 'wp_secure_checkout_api', 'api_settings');
    add_settings_field('wp_secure_checkout_api_setting_card_cvv', 'Card CVV', 'wp_secure_checkout_api_setting_card_cvv', 'wp_secure_checkout_api', 'api_settings');
}

add_action('admin_init', 'sca_register_settings');

function wp_secure_checkout_api_options_validate($input)
{
    $newinput['api_key'] = trim($input['api_key']);
    if (!preg_match('/^[a-z0-9]{40}$/i', $newinput['api_key'])) {
        $newinput['api_key'] = '';
    }
    $newinput['paypal_email'] = trim($input['paypal_email']);
    if (!preg_match('/^[a-z0-9 ]+$/i', $newinput['paypal_email'])) {
        $newinput['paypal_email'] = '';
    }
    $newinput['payment_method_bank'] = trim($input['payment_method_bank']);
    if (!preg_match('/^[a-z0-9 ]+$/i', $newinput['payment_method_bank'])) {
        $newinput['payment_method_bank'] = '';
    }
    $newinput['payment_method_paypal'] = trim($input['payment_method_paypal']);
    if (!preg_match('/^[a-z0-9 ]+$/i', $newinput['payment_method_paypal'])) {
        $newinput['payment_method_paypal'] = '';
    }
    $newinput['payment_method_card'] = trim($input['payment_method_card']);
    if (!preg_match('/^[a-z0-9 \/]+$/i', $newinput['payment_method_card'])) {
        $newinput['payment_method_card'] = '';
    }
    $newinput['card_number'] = trim($input['card_number']);
    if (!preg_match('/^[a-z0-9 ]+$/i', $newinput['card_number'])) {
        $newinput['card_number'] = '';
    }
    $newinput['card_expiry'] = trim($input['card_expiry']);
    if (!preg_match('/^[a-z0-9 \/]+$/i', $newinput['card_expiry'])) {
        $newinput['card_expiry'] = '';
    }
    $newinput['card_cvv'] = trim($input['card_cvv']);
    if (!preg_match('/^[a-z0-9 ]+$/i', $newinput['card_cvv'])) {
        $newinput['card_cvv'] = '';
    }
    return $newinput;
}

function wp_secure_checkout_api_section_text()
{
    echo '<p>Here you can set all the options for using the Secure Checkout API</p>';
}

function wp_secure_checkout_api_setting_api_key()
{
    $options = get_option('wp_secure_checkout_api_options');
    echo "<input id='wp_secure_checkout_api_setting_api_key' name='wp_secure_checkout_api_options[api_key]' type='text' value='" . esc_attr($options['api_key']) . "' />";
}

function wp_secure_checkout_api_setting_payment_method_bank()
{
    $options = get_option('wp_secure_checkout_api_options');
    echo "<input id='wp_secure_checkout_api_setting_payment_method_bank' name='wp_secure_checkout_api_options[payment_method_bank]' type='text' value='" . esc_attr($options['payment_method_bank']) . "' />";
}

function wp_secure_checkout_api_setting_payment_method_paypal()
{
    $options = get_option('wp_secure_checkout_api_options');
    echo "<input id='wp_secure_checkout_api_setting_payment_method_paypal' name='wp_secure_checkout_api_options[payment_method_paypal]' type='text' value='" . esc_attr($options['payment_method_paypal']) . "' />";
}

function wp_secure_checkout_api_setting_paypal_email()
{
    $options = get_option('wp_secure_checkout_api_options');
    echo "<input id='wp_secure_checkout_api_setting_paypal_email' name='wp_secure_checkout_api_options[paypal_email]' type='text' value='" . esc_attr($options['paypal_email']) . "' />";
}

function wp_secure_checkout_api_setting_payment_method_card()
{
    $options = get_option('wp_secure_checkout_api_options');
    echo "<input id='wp_secure_checkout_api_setting_payment_method_card' name='wp_secure_checkout_api_options[payment_method_card]' type='text' value='" . esc_attr($options['payment_method_card']) . "' />";
}

function wp_secure_checkout_api_setting_card_number()
{
    $options = get_option('wp_secure_checkout_api_options');
    echo "<input id='wp_secure_checkout_api_setting_card_number' name='wp_secure_checkout_api_options[card_number]' type='text' value='" . esc_attr($options['card_number']) . "' />";
}

function wp_secure_checkout_api_setting_card_expiry()
{
    $options = get_option('wp_secure_checkout_api_options');
    echo "<input id='wp_secure_checkout_api_setting_card_expiry' name='wp_secure_checkout_api_options[card_expiry]' type='text' value='" . esc_attr($options['card_expiry']) . "' />";
}

function wp_secure_checkout_api_setting_card_cvv()
{
    $options = get_option('wp_secure_checkout_api_options');
    echo "<input id='wp_secure_checkout_api_setting_card_cvv' name='wp_secure_checkout_api_options[card_cvv]' type='text' value='" . esc_attr($options['card_cvv']) . "' />";
}
