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
    add_settings_section('api_settings', 'ID Settings', 'wp_secure_checkout_api_section_text', 'wp_secure_checkout_api');
    add_settings_field('wp_secure_checkout_api_setting_api_key', 'API Key', 'wp_secure_checkout_api_setting_api_key', 'wp_secure_checkout_api', 'api_settings');
}

add_action('admin_init', 'sca_register_settings');

function wp_secure_checkout_api_options_validate($input)
{
    $newinput['api_key'] = trim($input['api_key']);
    if (!preg_match('/^[a-z0-9]{40}$/i', $newinput['api_key'])) {
        $newinput['api_key'] = '';
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
