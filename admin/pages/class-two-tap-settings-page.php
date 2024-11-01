<?php

/**
 * TT_Admin class.
 */
class Two_Tap_Settings_Page
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->define_constants();

        do_action( 'two_tap_settings_page_loaded' );

    }

    public function define_constants()
    {
        tt_define('TT_DEPOSIT_FUNDS_URL', TT_URL_CORE . '/deposit');
    }

    public function twotap_general_settings_init()
    {
        register_setting( 'twotap_general_settings_page', 'twotap_markup_type' );
        register_setting( 'twotap_general_settings_page', 'twotap_markup_value' );
        register_setting( 'twotap_general_settings_page', 'twotap_fulfilment_markup' );
        register_setting( 'twotap_general_settings_page', 'twotap_auto_send_purchase' );
        register_setting( 'twotap_general_settings_page', 'twotap_product_failed_decision' );

        add_settings_section(
            'twotap_general_settings_section',
            __( 'General options', 'wordpress' ),
            [$this, 'twotap_general_settings_section_callback'],
            'twotap_general_settings_page'
        );

        add_settings_field(
            'twotap_markup_type',
            __( 'Markup type', 'wordpress' ),
            [$this, 'twotap_markup_type_render'],
            'twotap_general_settings_page',
            'twotap_general_settings_section'
        );

        add_settings_field(
            'twotap_markup_value',
            __( 'Markup', 'wordpress' ),
            [$this, 'twotap_markup_value_render'],
            'twotap_general_settings_page',
            'twotap_general_settings_section'
        );

        add_settings_field(
            'twotap_fulfilment_markup',
            __( 'International fulfilment center charge', 'wordpress' ),
            [$this, 'twotap_fulfilment_markup_render'],
            'twotap_general_settings_page',
            'twotap_general_settings_section'
        );

        add_settings_field(
            'twotap_auto_send_purchase',
            __( 'Auto send purchase to Two Tap', 'wordpress' ),
            [$this, 'twotap_auto_send_purchase_render'],
            'twotap_general_settings_page',
            'twotap_general_settings_section'
        );

        add_settings_field(
            'twotap_product_failed_decision',
            __( 'Out of stock decision', 'wordpress' ),
            [$this, 'twotap_product_failed_decision_render'],
            'twotap_general_settings_page',
            'twotap_general_settings_section'
        );
    }

    public function twotap_logistics_settings_init()
    {
        register_setting( 'twotap_logistics_settings_page', 'twotap_logistics_type' );
        register_setting( 'twotap_logistics_settings_page', 'twotap_international_logistics_enabled' );
        register_setting( 'twotap_logistics_settings_page', 'twotap_logistics_settings' );

        add_settings_section(
            'twotap_logistics_settings_section',
            __( 'Logistics', 'wordpress' ),
            [$this, 'twotap_logistics_settings_section_callback'],
            'twotap_logistics_settings_page'
        );

        add_settings_field(
            'twotap_international_logistics_enabled',
            __( 'International Support', 'wordpress' ),
            [$this, 'twotap_international_logistics_enabled_render'],
            'twotap_logistics_settings_page',
            'twotap_logistics_settings_section'
        );

        add_settings_field(
            'twotap_logistics_type',
            __( 'Shipping logistics', 'wordpress' ),
            [$this, 'twotap_logistics_type_render'],
            'twotap_logistics_settings_page',
            'twotap_logistics_settings_section'
        );

        add_settings_field(
            'twotap_logistics_settings',
            __( 'Own US shipping address', 'wordpress' ),
            [$this, 'twotap_logistics_settings_render'],
            'twotap_logistics_settings_page',
            'twotap_logistics_settings_section'
        );
    }

    public function twotap_billing_settings_init()
    {
        register_setting( 'twotap_billing_settings_page', 'twotap_billing_type' );
        register_setting( 'twotap_billing_settings_page', 'twotap_billing_info' );

        add_settings_section(
            'twotap_billing_settings_section',
            __( 'Billing', 'wordpress' ),
            [$this, 'twotap_billing_settings_section_callback'],
            'twotap_billing_settings_page'
        );

        add_settings_field(
            'twotap_deposit_status',
            __( 'Two Tap deposit', 'wordpress' ),
            [$this, 'twotap_deposit_status_render'],
            'twotap_billing_settings_page',
            'twotap_billing_settings_section'
        );

        add_settings_field(
            'twotap_current_plan',
            __( 'Current Plan', 'wordpress' ),
            [$this, 'twotap_current_plan_render'],
            'twotap_billing_settings_page',
            'twotap_billing_settings_section'
        );

        // add_settings_field(
        //     'twotap_billing_info',
        //     __( 'Billing information', 'wordpress' ),
        //     [$this, 'twotap_billing_info_render'],
        //     'twotap_billing_settings_page',
        //     'twotap_billing_settings_section'
        // );
    }

    public function twotap_api_settings_init()
    {
        register_setting( 'twotap_api_settings_page', 'twotap_public_token' );
        register_setting( 'twotap_api_settings_page', 'twotap_private_token' );
        register_setting( 'twotap_api_settings_page', 'wc_url' );
        register_setting( 'twotap_api_settings_page', 'wc_consumer_key' );
        register_setting( 'twotap_api_settings_page', 'wc_consumer_secret' );

        add_settings_section(
            'twotap_api_settings_section',
            __( 'Tokens & keys', 'wordpress' ),
            [$this, 'twotap_api_settings_section_callback'],
            'twotap_api_settings_page'
        );

        add_settings_field(
            'twotap_public_token',
            __( 'Two Tap public token', 'wordpress' ),
            [$this, 'twotap_public_token_render'],
            'twotap_api_settings_page',
            'twotap_api_settings_section'
        );

        add_settings_field(
            'twotap_private_token',
            __( 'Two Tap private token', 'wordpress' ),
            [$this, 'twotap_private_token_render'],
            'twotap_api_settings_page',
            'twotap_api_settings_section'
        );

        add_settings_field(
            'wc_url',
            __( 'WooCommerce API URL', 'wordpress' ),
            [$this, 'wc_url_render'],
            'twotap_api_settings_page',
            'twotap_api_settings_section'
        );

        add_settings_field(
            'wc_consumer_key',
            __( 'WooCommerce Consumer Key', 'wordpress' ),
            [$this, 'wc_consumer_key_render'],
            'twotap_api_settings_page',
            'twotap_api_settings_section'
        );

        add_settings_field(
            'wc_consumer_secret',
            __( 'WooCommerce Consumer Secret', 'wordpress' ),
            [$this, 'wc_consumer_secret_render'],
            'twotap_api_settings_page',
            'twotap_api_settings_section'
        );
    }

    public function twotap_markup_value_render()
    {
        ?>
        <input type='text' name='twotap_markup_value' id='twotap_markup_value' class="regular-text" value='<?=get_option( 'twotap_markup_value' )?>'> <span class="js-markup-info"></span>
        <?php
    }

    public function twotap_fulfilment_markup_render()
    {
        ?>
        <input type='text' name='twotap_fulfilment_markup' id='twotap_fulfilment_markup' class="regular-text" value='<?=get_option( 'twotap_fulfilment_markup' )?>'> <span><?=get_woocommerce_currency_symbol()?></span> <p class="description">Here you may add the charge from the fulfilment center that will be added to the clients order. <br> <strong>Applied only to international (non-US) orders.</strong></p>
        <?php
    }

    public function twotap_auto_send_purchase_render()
    {
        ?>
        <label for="twotap_auto_send_purchase">
            <input type='checkbox' name='twotap_auto_send_purchase' id='twotap_auto_send_purchase' class="regular-text" value='yes' <?=get_option( 'twotap_auto_send_purchase' ) == 'yes' ? 'checked' : ''?>> Auto send purchase
        </label>
        <p class="description">When a customer checks out an order containing Two Tap products the purchase is automatically sent to Two Tap to be processed.</p>
        <?php
    }

    public function twotap_product_failed_decision_render()
    {
        $value = get_option('twotap_product_failed_decision');
        ?>
        <select name="twotap_product_failed_decision" id="twotap_product_failed_decision">
            <option value="outofstock" <?php $this->selected($value, 'outofstock'); ?>>Mark product out of stock</option>
            <option value="trash" <?php $this->selected($value, 'trash'); ?>>Trash product</option>
            <option value="delete" <?php $this->selected($value, 'delete'); ?>>Delete product</option>
        </select>
        <p class="description">How will the product be updated in your store when it will go out of stock at our merchants.</p>
        <?php
    }

    public function twotap_markup_type_render()
    {
        $type = get_option( 'twotap_markup_type' );
        ?>
        <select name="twotap_markup_type" id="twotap_markup_type">
            <option value="none" <?php $this->selected($type, 'none'); ?>>none</option>
            <option value="percent" <?php $this->selected($type, 'percent'); ?>>percent</option>
            <option value="value" <?php $this->selected($type, 'value'); ?>>value (<?=get_woocommerce_currency_symbol()?>)</option>
        </select>
        <?php
    }

    public function twotap_international_logistics_enabled_render()
    {
        ?>
        <label for="twotap_international_logistics_enabled">
            <input type='checkbox' name='twotap_international_logistics_enabled' id='twotap_international_logistics_enabled' class="js-international-logistics-enabled regular-text" value='yes' <?=get_option( 'twotap_international_logistics_enabled' ) == 'yes' ? 'checked' : ''?>> International orders enabled
        </label>
        <p class="description">Allow orders to be placed with international destinations.</p>
        <?php
    }

    public function twotap_logistics_type_render()
    {
        $options = get_option( 'twotap_logistics_settings' );
        $type = get_option( 'twotap_logistics_type' );
        $twotap = new Two_Tap();
        $shipping_logistics = $twotap->shipping_logistics;
        ?>
        <div id="twotap-shipping-logistics-info">
            <select name="twotap_logistics_type" id="twotap_logistics_type">
                <?php foreach($shipping_logistics as $option) : ?>
                    <option value="<?=$option['name']?>" <?php $this->selected($type, $option['name']); ?> <?=($option['available'] ? '' : 'disabled="disabled"')?>><?=$option['title']?><?=($option['available'] ? '' : ' (coming soon)' )?></option>
                <?php endforeach ?>
            </select>

            <?php foreach ($shipping_logistics as $option) : ?>
                <div id="twotap-<?=$option['name']?>-notice" class="logistics-option">
                    <?php if ($option['description']) : ?>
                        <p class="description"><?=$option['description']?></p>
                    <?php endif ?>

                    <?php if (isset($option['pros']) && count($option['pros']) > 0) : ?>
                        <br>
                        <div><strong>Pros</strong></div>
                        <?php foreach ($option['pros'] as $pro) : ?>
                            <p><?=$pro?></p>
                        <?php endforeach ?>
                    <?php endif ?>

                    <?php if (isset($option['cons']) && count($option['cons']) > 0) : ?>
                        <br>
                        <div><strong>Cons</strong></div>
                        <?php foreach ($option['cons'] as $con) : ?>
                            <p><?=$con?></p>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            <?php endforeach ?>

        </div>
        <?php
    }

    public function twotap_logistics_settings_render()
    {
        $shipping_info = get_option( 'twotap_logistics_settings' );
        ?>

        <div id="twotap-shipping-info">

            <div class="js-shipping-details">

                <label for="twotap_logistics_settings_email" class="<?=(!isset($shipping_info['email']) || $shipping_info['email'] == '' ? 'has-error' : '')?>">
                    Email
                    <br>
                    <input type='text' name='twotap_logistics_settings[email]' id='twotap_logistics_settings_emai`l' class="regular-text" value='<?php echo isset($shipping_info['email']) ? $shipping_info['email'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_logistics_settings_first_name" class="<?=(!isset($shipping_info['shipping_first_name']) || $shipping_info['shipping_first_name'] == '' ? 'has-error' : '')?>">
                    First name
                    <br>
                    <input type='text' name='twotap_logistics_settings[shipping_first_name]' id='twotap_logistics_settings_first_name' class="regular-text" value='<?php echo isset($shipping_info['shipping_first_name']) ? $shipping_info['shipping_first_name'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_logistics_settings_last_name" class="<?=(!isset($shipping_info['shipping_last_name']) || $shipping_info['shipping_last_name'] == '' ? 'has-error' : '')?>">
                    Last name
                    <br>
                    <input type='text' name='twotap_logistics_settings[shipping_last_name]' id='twotap_logistics_settings_last_name' class="regular-text" value='<?php echo isset($shipping_info['shipping_last_name']) ? $shipping_info['shipping_last_name'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_logistics_settings_telephone" class="<?=(!isset($shipping_info['shipping_telephone']) || $shipping_info['shipping_telephone'] == '' ? 'has-error' : '')?>">
                    Telephone
                    <br>
                    <input type='text' name='twotap_logistics_settings[shipping_telephone]' id='twotap_logistics_settings_telephone' class="regular-text" value='<?php echo isset($shipping_info['shipping_telephone']) ? $shipping_info['shipping_telephone'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_logistics_settings_address" class="<?=(!isset($shipping_info['shipping_address']) || $shipping_info['shipping_address'] == '' ? 'has-error' : '')?>">
                    Address
                    <br>
                    <input type='text' name='twotap_logistics_settings[shipping_address]' id='twotap_logistics_settings_address' class="regular-text" value='<?php echo isset($shipping_info['shipping_address']) ? $shipping_info['shipping_address'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_logistics_settings_city" class="<?=(!isset($shipping_info['shipping_city']) || $shipping_info['shipping_city'] == '' ? 'has-error' : '')?>">
                    City
                    <br>
                    <input type='text' name='twotap_logistics_settings[shipping_city]' id='twotap_logistics_settings_city' class="regular-text" value='<?php echo isset($shipping_info['shipping_city']) ? $shipping_info['shipping_city'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_logistics_settings_state" class="<?=(!isset($shipping_info['shipping_state']) || $shipping_info['shipping_state'] == '' ? 'has-error' : '')?>">
                    State
                    <br>
                    <input type='text' name='twotap_logistics_settings[shipping_state]' id='twotap_logistics_settings_state' class="regular-text" value='<?php echo isset($shipping_info['shipping_state']) ? $shipping_info['shipping_state'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_logistics_settings_zip" class="<?=(!isset($shipping_info['shipping_zip']) || $shipping_info['shipping_zip'] == '' ? 'has-error' : '')?>">
                    Zip
                    <br>
                    <input type='text' name='twotap_logistics_settings[shipping_zip]' id='twotap_logistics_settings_zip' class="regular-text" value='<?php echo isset($shipping_info['shipping_zip']) ? $shipping_info['shipping_zip'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_logistics_settings_country">Country</label>
                <br>
                <select name="twotap_logistics_settings[shipping_country]" id="twotap_logistics_settings_country">
                    <option value="United States of America">United States of America</option>
                </select>

            </div>
        </div>
        <?php
    }

    public function twotap_deposit_status_render()
    {
        $tt_tokens = get_tt_tokens();
        ?>
        Current deposit: <span class="js-current-deposit" data-url="<?=TT_URL_API_APP_STATUS?>" data-token="<?=$tt_tokens['twotap_private_token']?>"><span class="spinner is-active clear-float"></span></span>
        <br>
        <br>
        <button data-href="<?=TT_DEPOSIT_FUNDS_URL . '/' . $tt_tokens['twotap_private_token']?>" class="button js-open-deposit-window" target="_blank">Deposit more funds</button>
        <?php
    }

    public function twotap_current_plan_render()
    {
        $tt_tokens = get_tt_tokens();
        ?>
        <div class="js-twotap-current-plan-container" data-url="<?=TT_URL_API_APP_STATUS?>" data-token="<?=$tt_tokens['twotap_private_token']?>">
            <div class="js-current-plan">
                <div class="js-twotap-plans row"></div>
                <span class="spinner is-active clear-float"></span>
            </div>
        </div>
        <?php
    }

    public function twotap_billing_info_render()
    {
        $billing_info = get_option('twotap_billing_info');
        ?>
        <div id="twotap-billing-info">

            <div class="js-billing-details">
                <label for="twotap_billing_info_first_name">
                    First name
                    <br>
                    <input type='text' name='twotap_billing_info[first_name]' id='twotap_billing_info_first_name' class="regular-text" value='<?php echo isset($billing_info['first_name']) ? $billing_info['first_name'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_billing_info_last_name">
                    Last name
                    <br>
                    <input type='text' name='twotap_billing_info[last_name]' id='twotap_billing_info_last_name' class="regular-text" value='<?php echo isset($billing_info['last_name']) ? $billing_info['last_name'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_billing_info_telephone">
                    Telephone
                    <br>
                    <input type='text' name='twotap_billing_info[telephone]' id='twotap_billing_info_telephone' class="regular-text" value='<?php echo isset($billing_info['telephone']) ? $billing_info['telephone'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_billing_info_address">
                    Address
                    <br>
                    <input type='text' name='twotap_billing_info[address]' id='twotap_billing_info_address' class="regular-text" value='<?php echo isset($billing_info['address']) ? $billing_info['address'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_billing_info_city">
                    City
                    <br>
                    <input type='text' name='twotap_billing_info[city]' id='twotap_billing_info_city' class="regular-text" value='<?php echo isset($billing_info['city']) ? $billing_info['city'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_billing_info_state">
                    State
                    <br>
                    <input type='text' name='twotap_billing_info[state]' id='twotap_billing_info_state' class="regular-text" value='<?php echo isset($billing_info['state']) ? $billing_info['state'] : ''; ?>'>
                </label>
                <br>
                <br>

                <label for="twotap_billing_info_zip">
                    Zip
                    <br>
                    <input type='text' name='twotap_billing_info[zip]' id='twotap_billing_info_zip' class="regular-text" value='<?php echo isset($billing_info['zip']) ? $billing_info['zip'] : ''; ?>'>
                </label>

                <input type="hidden" name="twotap_billing_info[country]" id="twotap_billing_info_country" value="United States of America"/>
            </div>
        </div>
        <?php
    }

    public function twotap_general_settings_section_callback()
    {
        echo __( 'Edit the plugins general settings here.', 'twotap' );
    }

    public function twotap_logistics_settings_section_callback()
    {
        echo __( 'Edit your logistics settings here.', 'twotap' );
    }

    public function twotap_billing_settings_section_callback()
    {
        echo __( 'View your billing info here.', 'twotap' );
    }

    public function twotap_public_token_render()
    {
        $env = env('TWOTAP_PUBLIC_TOKEN');
        $value = !$env ? get_option( 'twotap_public_token' ) : 'set on the environment';
        if($env) :
        ?>
        <input type='text' name='fake_twotap_public_token' class="regular-text" value='<?=$value?>' <?php if($env) : ?> disabled="disabled" <?php endif ?> >
        <?php else: ?>
        <input type='text' name='twotap_public_token' class="regular-text" value='<?=$value?>'>
        <?php
        endif;
    }

    public function twotap_private_token_render()
    {
        $env = env('TWOTAP_PRIVATE_TOKEN');
        $value = !$env ? get_option( 'twotap_private_token' ) : 'set on the environment';
        if($env) :
        ?>
        <input type='text' name='fake_twotap_private_token' class="regular-text" value='<?=$value?>' <?php if($env) : ?> disabled="disabled" <?php endif ?> >
        <?php else: ?>
        <input type='text' name='twotap_private_token' class="regular-text" value='<?=$value?>'>
        <?php
        endif;
    }

    public function wc_url_render()
    {
        $env = env('WC_URL');
        $value = !$env ? get_option( 'wc_url' ) : 'set on the environment';
        if($env) :
        ?>
        <input type='text' name='fake_wc_url' class="regular-text" value='<?=$value?>' <?php if($env) : ?> disabled="disabled" <?php endif ?> >
        <?php else: ?>
        <input type='text' name='wc_url' class="regular-text" value='<?=$value?>'>
        <?php
        endif;
    }

    public function wc_consumer_key_render()
    {
        $env = env('WC_CONSUMER_KEY');
        $value = !$env ? get_option( 'wc_consumer_key' ) : 'set on the environment';
        if($env) :
        ?>
        <input type='text' name='fake_wc_consumer_key' class="regular-text" value='<?=$value?>' <?php if($env) : ?> disabled="disabled" <?php endif ?> >
        <?php else: ?>
        <input type='text' name='wc_consumer_key' class="regular-text" value='<?=$value?>'>
        <?php
        endif;
    }

    public function wc_consumer_secret_render()
    {
        $env = env('WC_CONSUMER_SECRET');
        $value = !$env ? get_option( 'wc_consumer_secret' ) : 'set on the environment';
        if($env) :
        ?>
        <input type='text' name='fake_wc_consumer_secret' class="regular-text" value='<?=$value?>' <?php if($env) : ?> disabled="disabled" <?php endif ?> >
        <?php else: ?>
        <input type='text' name='wc_consumer_secret' class="regular-text" value='<?=$value?>'>
        <?php
        endif;
    }

    public function twotap_api_settings_section_callback()
    {
        echo __( 'You may edit the api credentials here.', 'twotap' );
    }

    public function selected($option, $value)
    {
        echo $option == $value ? 'selected' : '';
    }

}