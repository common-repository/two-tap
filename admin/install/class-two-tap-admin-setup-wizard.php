<?php

/**
 * WC_Admin_Setup_Wizard class.
 */
class Two_Tap_Admin_Setup_Wizard {

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

    /** @var string Currenct Step */
    private $step   = '';

    /** @var array Steps for the setup wizard */
    private $steps  = array();
    // private $twotap_login_url = 'https://core.twotap.com/publishers/zooxo1PhLa4iX5ieziuPhei9_from_ecom_plugins';

    /**
     * Hook in tabs.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        if ( current_user_can( 'manage_woocommerce' ) ) {
            $this->setup_wizard();
        }
    }
    /**
     * Add admin menus/screens.
     */
    public function admin_menus() {
        add_dashboard_page( '', '', 'manage_options', 'twotap-setup', '' );
    }

    /**
     * Show the setup wizard.
     */
    public function setup_wizard() {
        if ( empty( $_GET['page'] ) || 'twotap-setup' !== $_GET['page'] ) {
            return;
        }
        $default_steps = array(
            'introduction' => array(
                'name'    => __( 'Introduction', 'twotap' ),
                'view'    => array( $this, 'twotap_setup_introduction' ),
                'handler' => '',
            ),
            'twotap_account_setup' => array(
                'name'    => __( 'Two Tap account setup', 'twotap' ),
                'view'    => array( $this, 'twotap_account_setup' ),
                'handler' => array( $this, 'twotap_account_setup_save' ),
            ),
            'plugin_settings' => array(
                'name'    => __( 'Plugin settings', 'twotap' ),
                'view'    => array( $this, 'twotap_plugin_settings' ),
                'handler' => array( $this, 'twotap_plugin_settings_save' ),
            ),
            'get_woocommerce_keys' => array(
                'name'    => __( 'WooCommerce Setup', 'twotap' ),
                'view'    => array( $this, 'twotap_get_woocommerce_keys' ),
                'handler' => array( $this, 'twotap_get_woocommerce_keys_save' ),
            ),
            'ready' => array(
                'name'    => __( 'Ready!', 'twotap' ),
                'view'    => array( $this, 'twotap_ready' ),
                'handler' => '',
            ),
        );
        $this->steps = $default_steps;
        $this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );

        if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
            call_user_func( $this->steps[ $this->step ]['handler'], $this );
        }

        wp_enqueue_style( 'woocommerce_admin_styles', TT_PLUGIN_URL . '/admin/css/twotap-setup.css', array( 'dashicons', 'install'), WC_VERSION );
        ob_start();
        $this->setup_wizard_header();
        $this->setup_wizard_steps();
        $this->setup_wizard_content();
        $this->setup_wizard_footer();
        exit;
    }

    /**
     * Get the URL for the next step's screen.
     * @param string step   slug (default: current step)
     * @return string       URL for next step if a next step exists.
     *                      Admin URL if it's the last step.
     *                      Empty string on failure.
     * @since 3.0.0
     */
    public function get_next_step_link( $step = '' ) {
        if ( ! $step ) {
            $step = $this->step;
        }

        $keys = array_keys( $this->steps );
        if ( end( $keys ) === $step ) {
            return admin_url();
        }

        $step_index = array_search( $step, $keys );
        if ( false === $step_index ) {
            return '';
        }

        return add_query_arg( 'step', $keys[ $step_index + 1 ] );
    }

    /**
     * Setup Wizard Header.
     */
    public function setup_wizard_header() {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width" />
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title><?php esc_html_e( 'Two Tap &rsaquo; Setup Wizard', 'woocommerce' ); ?></title>
            <?php do_action( 'admin_print_styles' ); ?>
            <?php do_action( 'admin_head' ); ?>
        </head>
        <body class="twotap-setup wp-core-ui">
            <h1 id="twotap-logo">
                <a href="https://twotap.com/" target="_blank" title="Two Tap">
                    <img src="<?=TT_PLUGIN_URL?>/admin/img/twotap-logo.png" alt="Two Tap">
                </a>
            </h1>
        <?php
    }

    /**
     * Setup Wizard Footer.
     */
    public function setup_wizard_footer() {
        ?>
            <?php if ( 'next_steps' === $this->step ) : ?>
                <a class="twotap-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Return to the WordPress Dashboard', 'woocommerce' ); ?></a>
            <?php endif; ?>
            </body>
        </html>
        <?php
    }

    /**
     * Output the steps.
     */
    public function setup_wizard_steps() {
        $ouput_steps = $this->steps;
        array_shift( $ouput_steps );
        ?>
        <ol class="progress-track">
            <?php foreach ( $ouput_steps as $step_key => $step ) : ?>
                <li class="progress-step <?php
                    if ( $step_key === $this->step ) {
                        echo 'progress-done progress-current';
                    } elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
                        echo 'progress-done';
                    } else {
                        echo 'progress-todo';
                    }
                ?>">
                    <div class="progress-dot">
                    </div>
                    <span class="progress-text"><?php echo esc_html( $step['name'] ); ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php
    }

    /**
     * Output the content for the current step.
     */
    public function setup_wizard_content() {
        echo '<div class="twotap-setup-content">';
        call_user_func( $this->steps[ $this->step ]['view'], $this );
        echo '</div>';
    }

    public function twotap_setup_introduction()
    {
        ?>
        <h1><?php esc_html_e( 'Thank you for choosing Two Tap!', 'twotap' ); ?></h1>
        <div class="tt-info">
            <p><?php _e( 'Two Tap is a dropshipping solution that allows you to sell US products from well known brands and retailers.', 'twotap' ); ?></p>
            <p><?php esc_html_e( 'Setup is easy:', 'twotap' ); ?></p>
            <ol>
                <li><?php _e( 'First you’ll import US products to your regular WooCommerce setup.', 'twotap' ); ?></li>
                <li><?php _e( 'Customers will place orders for those products just like any other WooCommerce item in your inventory.', 'twotap' ); ?></li>
                <li><?php _e( 'Two Tap can then either ship the products directly to the customer, to an international warehouse address, or to your US freight forwarder.', 'twotap' ); ?></li>
            </ol>
            <h4>Pricing</h4>
            <p>You are able to add your own mark-up to the retailer prices and process payments with your existing payment processor.</p>
            <p class="js-plan-description" style="display: none;">Two Tap costs $<span class="js-plan-cost"></span>/month, with the billing period starting when you process your first Two Tap order. <strong>You don’t pay anything until the first order</strong>.</p>
            <p>When you get your first order you will add funds to your Two Tap balance to allow us to place the order for the product. Funds can be added via Wire Transfers at this time, with more payment methods coming soon.</p>
            <p>You will start on the <strong>free</strong> plan and will automatically be upgraded once you hit the threshold.</p>
            <div class="plans js-plans"><div class="row"></div></div>

            <div class="clear"></div>
            <p>For any questions feel free to email <a href="mailto:support@twotap.com" title="Two Tap Support">support@twotap.com</a> or join our <a href="https://ttdropship.herokuapp.com/" target="_blank" title="Two Tap Dropshipping community">Slack community</a>.</p>
        </div>

        <p class="tt-setup-actions step">
            <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php esc_html_e( 'Let\'s go!', 'twotap' ); ?></a>
            <a href="<?php echo esc_url( admin_url() ); ?>" class="button button-large"><?php esc_html_e( 'Not right now', 'twotap' ); ?></a>
        </p>
        <script src="<?=TT_PLUGIN_URL?>/admin/js/vendor/jquery.min.js"></script>
        <script src="<?=TT_PLUGIN_URL?>/admin/js/vendor/underscore.min.js"></script>
        <script>
            var $ = jQuery.noConflict();
            var App = {
                getInfo: function () {
                    var ajax_url = "<?=admin_url( 'admin-ajax.php' )?>";
                    var data = {
                        action: 'twotap_get_plans',
                        info_type: 'onboarding_step_one',
                    };
                    $.ajax({
                        type: "POST",
                        url: ajax_url,
                        data: data,
                        timeout: 5000,
                        success: function(response){
                            // console.log(response);
                            if(!_.isUndefined(response.plans) && response.plans.length > 0){
                                App.setupPlans(response.plans);
                            }
                        },
                        complete: function () {
                            App.enableLinks();
                        }
                    });
                },
                setupPlans: function(plans){
                    var planTemplate = '<div class="col-sm-4"><div class="plan" data-plan-id="PLAN_ID"><div class="plan-name">PLAN_NAME</div></div></div>';
                    var response = '';
                    _.each(plans, function(plan){
                        var planBody = planTemplate.replace(/PLAN_ID/, plan.id);
                        plan.name = plan.name.replace(/\//g, '<br />');
                        plan.name = plan.name.replace(/free/, '<strong>free</strong>');
                        plan.name = plan.name.replace(/unlimited/g, '<strong>unlimited</strong>');
                        planBody = planBody.replace(/PLAN_NAME/, plan.name);
                        planBody = planBody.replace(/PLAN_PRICE/, plan.price_per_month);
                        response += planBody;
                    });
                    $('.js-plans .row').html(response);
                    $('.js-plans .row .plan').first().addClass('active');
                    equalHeight('.js-plans .row .plan');
                },
                enableLinks: function () {
                    $('[data-href]').each(function () {
                        var $this = $(this);
                        var href = $this.attr('data-href');
                        $this.attr('href', href).removeAttr('data-href').removeAttr('disabled');
                    });
                }
            };

            jQuery(document).ready(function($) {
                App.getInfo();
            });
            /* Thanks to CSS Tricks for pointing out this bit of jQuery
             http://css-tricks.com/equal-height-blocks-in-rows/
             It's been modified into a function called at page load and then each time the page is resized. One large modification was to remove the set height before each new calculation. */

            var equalHeight = function (container)
            {

               var currentTallest  = 0,
                   currentRowStart = 0,
                   rowDivs         = [],
                   $el,
                   topPosition     = 0;
               $(container).each(function ()
               {

                   $el = $(this);
                   $($el).height('auto');
                   var topPostion = $el.position().top;

                   if ( currentRowStart !== topPostion ) {
                       for (var currentDiv = 0; currentDiv < rowDivs.length; currentDiv++) {
                           rowDivs[currentDiv].height(currentTallest);
                       }
                       rowDivs.length = 0; // empty the array
                       currentRowStart = topPostion;
                       currentTallest = $el.height();
                       rowDivs.push($el);
                   } else {
                       rowDivs.push($el);
                       currentTallest = (currentTallest < $el.height()) ? ($el.height()) : (currentTallest);
                   }
                   for (var nextDiv = 0; nextDiv < rowDivs.length; nextDiv++) {
                       rowDivs[nextDiv].height(currentTallest);
                   }
               });
            };
        </script>
        <?php
    }

    public function twotap_account_setup()
    {
        ?>
        <div class="twotap-sign-up-form">
            <h1><?php esc_html_e( 'Sign up for a Two Tap account', 'twotap' ); ?></h1>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="email">E-mail</label>
                        </th>
                        <td>
                            <input name="email" type="text" id="email" size="25" value="<?=wp_get_current_user()->user_email?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="password">Password</label>
                        </th>
                        <td>
                            <input name="password" type="password" id="password" size="25" value="">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="password_confirmation">Password confirmation</label>
                        </th>
                        <td>
                            <input name="password_confirmation" type="password" id="password_confirmation" size="25" value="">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="name">Your name</label>
                        </th>
                        <td>
                            <input name="name" type="text" id="name" size="25" value="<?=wp_get_current_user()->display_name?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="store_name">Store name</label>
                        </th>
                        <td>
                            <input name="store_name" type="text" id="store_name" size="25" value="<?php bloginfo('site_name'); ?>">
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="js-hidden-notice-create" style="display: none; color: red;"></p>
            <p>
                <a href="javascript:void(0);" title="Set up your tokens" class="js-toggle-forms">I already have a Two Tap account</a>
            </p>
            <p>
                <a href="javascript:void(0)" class="button-primary button button-large button-next js-create-account"><?php esc_html_e( 'Submit' ); ?></a>
                <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_html_e( 'Skip step', 'twotap' ); ?></a>
            </p>
        </div>
        <div class="twotap-sign-in-form" style="display: none;">
            <h1><?php esc_html_e( 'Update your Two Tap tokens', 'twotap' ); ?></h1>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="public_token">Two Tap public token</label>
                        </th>
                        <td>
                            <input name="public_token" type="text" id="public_token" size="25" value="">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="private_token">Two Tap private token</label>
                        </th>
                        <td>
                            <input name="private_token" type="text" id="private_token" size="25" value="">
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="js-hidden-notice-update" style="display: none; color: red;"></p>
            <p>
                Please <a href="https://core.twotap.com/buyers/sign_in" title="Two Tap publishers sign in" target="_blank">sign in</a> into your Two Tap account and retrieve your tokens.
            </p>
            <p>
                <a href="javascript:void(0);" title="Set up your tokens" class="js-toggle-forms">I need to create a Two Tap account</a>
            </p>
            <p>
                <a href="javascript:void(0)" class="button-primary button button-large button-next js-save-tokens"><?php esc_html_e( 'Submit' ); ?></a>
                <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_html_e( 'Skip step', 'twotap' ); ?></a>
            </p>
        </div>
        <div class="twotap-account-success" style="display: none;">
            <h1><?php esc_html_e( 'Succesfully created the account', 'twotap' ); ?></h1>
            <p>You may login and review your account <a href="https://core.twotap.com/buyers/sign_in" title="Two Tap publishers sign in">here</a>.</p>
            <p>Your apps tokens are already updated.</p>
            <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php esc_html_e( 'Next step', 'twotap' ); ?></a>
        </div>
        <div class="twotap-tokens-success" style="display: none;">
            <h1><?php esc_html_e( 'Succesfully set tokens', 'twotap' ); ?></h1>
            <p>The Two Tap tokens have been updated. </p>
            <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php esc_html_e( 'Next step', 'twotap' ); ?></a>
        </div>
        <script src="<?=TT_PLUGIN_URL?>/admin/js/vendor/jquery.min.js"></script>

        <script type="text/javascript">
            var $ = jQuery.noConflict();
            var App = {
                context: 'create',

                createAccount: function () {
                    $('.js-hidden-notice-create').hide();
                    var ajax_url = "<?=admin_url( 'admin-ajax.php' )?>";
                    var email = $('#email').val();
                    var password = $('#password').val();
                    var password_confirmation = $('#password_confirmation').val();
                    var name = $('#name').val();
                    var store_name = $('#store_name').val();

                    if(email == '' || name == '' || store_name == '' || password == '' || password_confirmation == '' || store_name == ''){
                        $('.js-hidden-notice-create').show().text('Please fill in all your details.');
                        return;
                    }

                    var data = {
                        action: 'twotap_setup_create_account',
                        email: email,
                        password: password,
                        password_confirmation: password_confirmation,
                        name: name,
                        store_name: store_name,
                    };
                    $.post(ajax_url, data, function(response){
                        if( response.success ){
                            $('.twotap-sign-up-form').hide();
                            $('.twotap-sign-in-form').hide();
                            $('.twotap-account-success').show();
                        } else {
                            $('.js-hidden-notice-create').show().text(response.message);
                        }
                    });
                },
                toggleForms: function () {
                    var $signUpForm = $('.twotap-sign-up-form');
                    var $signInForm = $('.twotap-sign-in-form');
                    if($signUpForm.is(':visible')){
                        $signUpForm.hide();
                        $signInForm.show();
                        App.context = 'update';
                    } else {
                        $signInForm.hide();
                        $signUpForm.show();
                        App.context = 'create';
                    }
                },
                saveTokens: function () {
                    $('.js-hidden-notice-update').hide();
                    var ajax_url = "<?=admin_url( 'admin-ajax.php' )?>";
                    var public_token = $('#public_token').val();
                    var private_token = $('#private_token').val();

                    if(public_token == '' || private_token == ''){
                        $('.js-hidden-notice-update').show().text('Please fill in the public and private tokens.');
                        return;
                    }

                    var data = {
                        action: 'twotap_setup_save_tokens',
                        public_token: $('#public_token').val(),
                        private_token: $('#private_token').val(),
                    };
                    $.post(ajax_url, data, function(response){
                        if( response.success ){
                            $('.twotap-sign-up-form').hide();
                            $('.twotap-sign-in-form').hide();
                            $('.twotap-tokens-success').show();
                        }
                    });
                },
            };

            jQuery(document).ready(function ($) {
                $(document).on('click', '.js-create-account', App.createAccount);
                $(document).on('click', '.js-toggle-forms', App.toggleForms);
                $(document).on('click', '.js-save-tokens', App.saveTokens);
            });

        </script>
        <?php
    }

    public function twotap_plugin_settings()
    {
        $twotap = new Two_Tap();
        $shipping_logistics = $twotap->shipping_logistics;
        ?>
        <div class="twotap-settings-form">
            <h1><?php esc_html_e( 'Configure plugin', 'twotap' ); ?></h1>
                <h3 style="margin-bottom: 0; padding-bottom: 0; padding-top: 20px;">Enable crossborder shipping?</h3>
            <p>
                Currently, products can be sent only to US addresses. However, crossborder shipping is still possible if you are using third party freight forwarders (eg. myus). Two Tap can ship all orders to a US fulfilment center where you will be able to manage the international shipping.
            </p>
            <p>
                <label>
                    <input type="checkbox" value="yes" class="js-international-logistics-enabled" <?=get_option( 'twotap_international_logistics_enabled' ) == 'yes' ? 'checked' : ''?>> I'm going to use a freight forwarder to ship internationally.
                </label>
                <br>
            <span class="description" style="color: #999; font-size: 12px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; * Seamless end to end crossborder shipping coming soon</span>
            </p>
            <div class="js-international-orders-enabled-container">
                <p>Choose between using your own logistics infrastructure (eg. MyUS), or have Two Tap ship the products internationally.</p>
                <p><strong>Important</strong>: If you are using your own US logistics infrastructure Two Tap will give your periodic cashbacks from retailer commissions.</p>
                <?php foreach($shipping_logistics as $option) : ?>
                    <div class="pros-cons-option <?php if (!$option['available']) : ?> disabled <?php endif; ?>">
                        <span class='pros-cons-title'>
                            <label>
                                <input type="radio" name="logistics_type" class="logistics-type-option" id="logistics_type_<?=$option['name']?>" value="<?=$option['name']?>" <?php if (!$option['available']) : ?> disabled="disabled" <?php endif; ?>/>
                                <strong><?=$option['title']?> <?php if (!$option['available']) : ?> (coming soon)<?php endif; ?></strong>
                            </label>
                        </span>
                        <?php if (isset($option['pros']) && count($option['pros']) > 0) : ?>
                            <div class="title">
                                <strong class="text-success">Pros</strong>
                            </div>
                            <ul>
                                <?php foreach ($option['pros'] as $pro) : ?>
                                    <li><?=$pro?></li>
                                <?php endforeach ?>
                            </ul>
                            <div class="clear"></div>
                        <?php endif ?>

                        <?php if (isset($option['cons']) && count($option['cons']) > 0) : ?>
                            <div class="title">
                                <strong class="text-danger">Cons</strong>
                            </div>
                            <ul>
                                <?php foreach ($option['cons'] as $con) : ?>
                                    <li><?=$con?></li>
                                <?php endforeach ?>
                            </ul>
                            <div class="clear"></div>
                        <?php endif ?>
                    </div>

                <?php endforeach?>
                <div class="clearfix"></div>
                <table class="form-table js-logistics-table" style="display: none;">
                    <tbody>
                        <tr>
                            <?php $logistics_settings = get_option( 'twotap_logistics_settings' ); ?>
                            <th scope="row">Logistics Info</th>
                            <td>
                                <div id="twotap-logistics-info" class="one-of-two">

                                    <label for="twotap_logistics_settings_shipping_first_name">
                                        First name
                                        <br>
                                        <input type='text' name='twotap_logistics_settings[shipping_first_name]' id='twotap_logistics_settings_shipping_first_name' class="regular-text" value='<?php echo isset($logistics_settings['shipping_first_name']) ? $logistics_settings['shipping_first_name'] : '' ?>'>
                                    </label>
                                    <br>
                                    <br>

                                    <label for="twotap_logistics_settings_shipping_last_name">
                                        Last name
                                        <br>
                                        <input type='text' name='twotap_logistics_settings[shipping_last_name]' id='twotap_logistics_settings_shipping_last_name' class="regular-text" value='<?php echo isset($logistics_settings['shipping_last_name']) ? $logistics_settings['shipping_last_name'] : '' ?>'>
                                    </label>
                                    <br>
                                    <br>

                                    <label for="twotap_logistics_settings_shipping_telephone">
                                        Telephone
                                        <br>
                                        <input type='text' name='twotap_logistics_settings[shipping_telephone]' id='twotap_logistics_settings_shipping_telephone' class="regular-text" value='<?php echo isset($logistics_settings['shipping_telephone']) ? $logistics_settings['shipping_telephone'] : '' ?>'>
                                    </label>
                                    <br>
                                    <br>

                                    <label for="twotap_logistics_settings_email">
                                        E-mail
                                        <br>
                                        <input type='text' name='twotap_logistics_settings_email' id='twotap_logistics_settings_email' class="regular-text" value='<?php echo isset($logistics_settings['email']) ? $logistics_settings['email'] : '' ?>'>
                                    </label>
                                    <br>
                                    <br>
                                </div>
                                <div id="twotap-logistics-info" class="one-of-two">

                                    <label for="twotap_logistics_settings_shipping_address">
                                        Address
                                        <br>
                                        <input type='text' name='twotap_logistics_settings[shipping_address]' id='twotap_logistics_settings_shipping_address' class="regular-text" value='<?php echo isset($logistics_settings['shipping_address']) ? $logistics_settings['shipping_address'] : '' ?>'>
                                    </label>
                                    <br>
                                    <br>

                                    <label for="twotap_logistics_settings_shipping_city">
                                        City
                                        <br>
                                        <input type='text' name='twotap_logistics_settings[shipping_city]' id='twotap_logistics_settings_shipping_city' class="regular-text" value='<?php echo isset($logistics_settings['shipping_city']) ? $logistics_settings['shipping_city'] : '' ?>'>
                                    </label>
                                    <br>
                                    <br>

                                    <label for="twotap_logistics_settings_shipping_state">
                                        State
                                        <br>
                                        <input type='text' name='twotap_logistics_settings[shipping_state]' id='twotap_logistics_settings_shipping_state' class="regular-text" value='<?php echo isset($logistics_settings['shipping_state']) ? $logistics_settings['shipping_state'] : '' ?>'>
                                    </label>
                                    <br>
                                    <br>

                                    <label for="twotap_logistics_settings_shipping_zip">
                                        Zip
                                        <br>
                                        <input type='text' name='twotap_logistics_settings[shipping_zip]' id='twotap_logistics_settings_shipping_zip' class="regular-text" value='<?php echo isset($logistics_settings['shipping_zip']) ? $logistics_settings['shipping_zip'] : '' ?>'>
                                    </label>
                                    <br>
                                    <br>

                                    <label for="twotap_logistics_settings_shipping_country">Country</label>
                                    <br>
                                    <select name="twotap_logistics_settings[shipping_country]" id="twotap_logistics_settings_shipping_country">
                                        <option value="United States of America">United States of America</option>
                                    </select>

                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="twotap_markup_type">Markup type</label>
                            </th>
                            <td>
                                <?php
                                    $twotap_markup_type = get_option( 'twotap_markup_type' );
                                    $twotap_markup_value = get_option( 'twotap_markup_value' );
                                ?>
                                <select name="twotap_markup_type" id="twotap_markup_type">
                                    <option value="none" <?php $this->selected($twotap_markup_type, 'none'); ?>>none</option>
                                    <option value="percent" <?php $this->selected($twotap_markup_type, 'percent'); ?>>percent</option>
                                    <option value="value" <?php $this->selected($twotap_markup_type, 'value'); ?>>value</option>
                                </select>
                                <p class="description">The type markup would you like to apply on Two Tap imported products.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="twotap_markup_value">Markup value</label>
                            </th>
                            <td>
                                <input type='text' name='twotap_markup_value' id='twotap_markup_value' class="regular-text" value="<?=$twotap_markup_value?>"> <span class="js-markup-info"></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="description">You can also update this information later in the settings page.</p>
            <p class="js-hidden-notice" style="display: none; color: red;"></p>
            <p>
                <a href="javascript:void(0)" class="button-primary button button-large button-next js-update-settings"><?php esc_html_e( 'Submit' ); ?></a>
                <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_html_e( 'Skip step', 'twotap' ); ?></a>
            </p>
        </div>
        <div class="twotap-settings-success" style="display: none;">
            <h1><?php esc_html_e( 'Succesfully updated plugin settings', 'twotap' ); ?></h1>
            <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php esc_html_e( 'Next step', 'twotap' ); ?></a>
        </div>
        <script src="<?=TT_PLUGIN_URL?>/admin/js/vendor/jquery.min.js"></script>
        <script src="<?=TT_PLUGIN_URL?>/admin/js/vendor/underscore.min.js"></script>

        <script type="text/javascript">
            var tt_settings_vars = {
                'woocommerce_currency': "<?php echo get_woocommerce_currency()?>",
                'woocommerce_currency_symbol': "<?php echo html_entity_decode(get_woocommerce_currency_symbol())?>",
            };
            var $ = jQuery.noConflict();
            var App = {
                logisticsSelected: function () {
                    var $this = $(this);
                    var option = $this.val();
                    if ($('table.js-logistics-table').length > 0) {
                        $('table.js-logistics-table').show();
                        $('body,html').animate({
                            scrollTop: $('table.js-logistics-table').offset().top
                          }, 700
                        );
                        App.changeMarkupType();
                    }
                },
                changeMarkupType: function () {
                  var option = $('#twotap_markup_type').val();

                  switch(option) {
                    case 'percent':
                      $('.js-markup-info').text('%');
                      $('#twotap_markup_value').closest('tr').show();
                      break;
                    case 'value':
                      $('.js-markup-info').text(tt_settings_vars.woocommerce_currency_symbol);
                      $('#twotap_markup_value').closest('tr').show();
                      break;
                    default:
                    case 'none':
                      $('#twotap_markup_value').closest('tr').hide();
                      $('.js-markup-info').text('');
                  }
                },
                changeInternationalToggle: function () {
                  var checked = $('.js-international-logistics-enabled').is(':checked');

                  if (checked) {
                    $('.js-international-orders-enabled-container').slideDown(150);
                  } else {
                    $('.js-international-orders-enabled-container').slideUp(150);
                  }

                },
                updateSettings: function () {
                    $('.js-hidden-notice').hide();
                    var ajax_url = "<?=admin_url( 'admin-ajax.php' )?>";
                    var fields = [
                        // 'store_country',
                        // 'twotap_logistics_settings_billing_first_name',
                        // 'twotap_logistics_settings_billing_last_name',
                        // 'twotap_logistics_settings_billing_address',
                        // 'twotap_logistics_settings_billing_city',
                        // 'twotap_logistics_settings_billing_state',
                        // 'twotap_logistics_settings_billing_zip',
                        // 'twotap_logistics_settings_billing_country',
                        // 'twotap_logistics_settings_billing_telephone',
                        'logistics_type',
                        'twotap_logistics_settings_email',
                        'twotap_logistics_settings_shipping_first_name',
                        'twotap_logistics_settings_shipping_last_name',
                        'twotap_logistics_settings_shipping_address',
                        'twotap_logistics_settings_shipping_city',
                        'twotap_logistics_settings_shipping_state',
                        'twotap_logistics_settings_shipping_zip',
                        'twotap_logistics_settings_shipping_country',
                        'twotap_logistics_settings_shipping_telephone',
                        'twotap_markup_type',
                        'twotap_markup_value'
                    ];

                    var moveOn = true;
                    _.each(fields, function(field) {
                        if (field == 'twotap_markup_value' && $('#twotap_markup_type').val() == 'none') {
                            return;
                        }
                        if ($('#'+field).val() == '') {
                            moveOn = false;
                        }
                    });
                    if (!moveOn) {
                        $('.js-hidden-notice').show().text('Please fill in all your details.');
                        return;
                    }
                    var plugin_settings = {};
                    _.each( fields, function (field) {
                        plugin_settings[field] = $('#'+field).val();
                    });
                    plugin_settings.logistics_type = $('input[name="logistics_type"]').val();
                    plugin_settings.intl_enabled = $('.js-international-logistics-enabled').is(':checked');

                    var data = {
                        action: 'twotap_setup_plugin_settings',
                        plugin_settings: plugin_settings,
                    };
                    $.post(ajax_url, data, function(response) {
                        if( response.success ){
                            $('.twotap-settings-form').hide();
                            $('.twotap-sign-in-form').hide();
                            $('.twotap-settings-success').show();
                        } else {
                            $('.js-hidden-notice').show().text(response.message);
                        }
                    });
                },

                useSameBillingDetails: function(e){
                  e.preventDefault();
                  e.stopPropagation();
                  var fields = ['first_name', 'last_name', 'address', 'city', 'state', 'zip', 'country', 'telephone' ];
                  _.each(fields, function(field){
                    var value = $('#twotap_logistics_settings_shipping_' + field).val();
                    $('#twotap_logistics_settings_billing_' + field).val(value);
                  });
                },

            };

            jQuery(document).ready(function($) {
                if ($('.js-international-logistics-enabled').length > 0) {
                    App.changeInternationalToggle();
                }

                App.changeMarkupType();

                $(document).on('click', '.js-update-settings', App.updateSettings);
                $(document).on('click', '.js-use-shipping-details', App.useSameBillingDetails);
                $(document).on('click', '.js-international-logistics-enabled', App.changeInternationalToggle);
                $(document).on('click', '[name="logistics_type"]', App.logisticsSelected);
                $(document).on('change', '#twotap_markup_type', App.changeMarkupType);
            });

        </script>
        <?php
    }

    public function twotap_get_woocommerce_keys()
    {
        $force_keys = isset($_GET['force']) && $_GET['force'] == 1;
        $success = isset($_GET['success']) && $_GET['success'] == 1;

        if( !wc_tokens_set() || $force_keys ){

            ?>
            <div class="twotap-setup-wc-keys-form">
                <h1><?php esc_html_e( 'Setup the WooCommerce keys', 'twotap' ); ?></h1>
                <p>Click the button below and follow the instructions to retrieve the woocommerce keys.</p>
            <?php
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
                // SSL connection

                $endpoint = site_url('/wc-auth/v1/authorize');
                $params = [
                    'app_name' => 'Two Tap',
                    'scope' => 'read_write',
                    'user_id' => get_current_user_id(),
                    'return_url' => site_url('/wp-admin/admin.php?page=twotap-setup&step=get_woocommerce_keys'),
                    'callback_url' => site_url('/wp-json/two_tap/woocommerce_keys', 'https')
                ];
                $query_string = http_build_query( $params );

                $url = $endpoint . '?' . $query_string;
                ?>
                <a href="<?=$url?>" class="button button-primary button-wc" title="Authorize with WooCommerce">Authorize with WooCommerce</a>
                <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_html_e( 'Skip step', 'twotap' ); ?></a>

            <?php } else { ?>

                <p>Please head to WooCommerce <a href="<?=site_url('/wp-admin/admin.php?page=wc-settings&tab=api&section=keys')?>" target="_blank">REST API keys section</a> and generate the necessary keys <strong>with Read/Write permissions</strong>.</p>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="consumer_key">WooCommerce consumer key</label>
                            </th>
                            <td>
                                <input name="consumer_key" type="text" id="consumer_key" size="25" value="">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="consumer_secret">WooCommerce consumer secret</label>
                            </th>
                            <td>
                                <input name="consumer_secret" type="text" id="consumer_secret" size="25" value="">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="js-hidden-notice" style="display: none; color: red;"></p>
                <p>
                    <a href="javascript:void(0)" class="button-primary button button-large button-next js-submit-keys"><?php esc_html_e( 'Submit' ); ?></a>
                    <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php esc_html_e( 'Skip step', 'twotap' ); ?></a>
                </p>
            </div>
            <div class="twotap-tokens-success" style="display: none;">
                <h1><?php esc_html_e( 'Succesfully set WooCommerce keys', 'twotap' ); ?></h1>
                <p>Now you can import, update &amp; sell Two Tap products throught WooCommerce.</p>
                <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php esc_html_e( 'Next step', 'twotap' ); ?></a>
            </div>
            <script src="<?=TT_PLUGIN_URL?>/admin/js/vendor/jquery.min.js"></script>

            <script type="text/javascript">
                var $ = jQuery.noConflict();
                var App = {
                    submitKeys: function () {
                        var ajax_url = "<?=admin_url( 'admin-ajax.php' )?>";
                        var consumer_key = $('#consumer_key').val();
                        var consumer_secret = $('#consumer_secret').val();

                        if(consumer_key == '' || consumer_secret == ''){
                            $('.js-hidden-notice').show().text('Please fill in all your details.');
                            return;
                        }

                        var data = {
                            action: 'twotap_setup_save_wc_tokens',
                            consumer_key: consumer_key,
                            consumer_secret: consumer_secret,
                        };
                        $.post(ajax_url, data, function(response){
                            if( response.success ){
                                App.succesfullySetTokens();
                            }
                        });
                    },

                    succesfullySetTokens: function () {
                        $('.twotap-setup-wc-keys-form').hide();
                        $('.twotap-tokens-success').show();
                    },
                };

                jQuery(document).ready(function($) {
                    $(document).on('click', '.js-submit-keys', App.submitKeys);
                });

            </script>
            <?php
            }
            ?>
            <?php
        } else {
            ?>
            <?php if($success): ?>
                <h1><?php esc_html_e( 'Succesfully set WooCommerce keys', 'twotap' ); ?></h1>
                <p>Now you can import, update &amp; sell Two Tap products throught WooCommerce.</p>
            <?php else: ?>
                <p>The WooCommerce keys appear to be set.</p>
                <p>If you still wish to issue new keys <a href="<?=site_url('/wp-admin/admin.php?page=twotap-setup&step=get_woocommerce_keys&force=1')?>">click here</a>.</p>
            <?php endif; ?>
            <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next"><?php esc_html_e( 'Next step', 'twotap' ); ?></a>
            <br>
            <br>
            <?php
        }
    }

    public function twotap_ready()
    {
        $twotap_admin = new Two_Tap_Admin($this->plugin_name, $this->version);
        $twotap_admin->check_wc_api_enabled();
        $webhooks_created = false;
        if(wc_tokens_set() && $twotap_admin->check_wc_api_enabled() && !$twotap_admin->check_wc_webhooks_ok()){
            $data = [
                'name' => 'Order created for Two Tap',
                'topic' => 'order.created',
                'status' => 'active',
                'secret' => 'twotap_wc_secret',
                'delivery_url' => site_url('wp-json/two_tap/wc_order_created')
            ];
            global $wc_api;
            try{
                $response = $wc_api->post('webhooks', $data);
                $webhooks_created = true;
            }catch(Exception $e){
                $response = false;
                $webhooks_created = false;
                l('WC Webhooks weren\'t created.', $e->getResponse() );
            }
            update_option(TT_OPTION_WEBHOOKS_OK, true);
        }

        $url = 'https://twotap.com/ecommerce-plugins/';
        $tweet = 'Just installed Two Tap Product Catalog to increase my eCommerce inventory and start my dropshipping business.'

        ?>
        <h1><?php esc_html_e( 'Congratulations', 'twotap' ); ?></h1>
        <p><strong>What's next?</strong></p>

        <ul>
            <li>Head over and <a href="<?=site_url('/wp-admin/admin.php?page=' . TT_PRODUCTS_PAGE)?>" title="Import Two Tap products">import Two Tap products</a></li>
            <li><a href="<?=site_url('/wp-admin/admin.php?page=' . TT_SETTINGS_PAGE)?>" title="Set a markup on imported products">Set a markup</a></li>
            <li>Send us a message on our <a href="https://ttdropship.herokuapp.com/" title="Two Tap Dropshipping Slack Channel">Slack channel</a> or on the <a href="http://community.twotap.com" target="_blank" title="Two Tap Dropshipping Community Forum">Two Tap Community Forum</a></li>
            <li><a href="javascript:void(0);" class="js-click-to-tweet">Tweet about your progress</a></li>
        </ul>

        <a href="<?=site_url('/wp-admin/admin.php?page=' . TT_PRODUCTS_PAGE)?>" title="Two Tap products" class="button-primary button button-large button-next"><?php esc_html_e( 'Import products', 'twotap' ); ?></a>
        <a href="<?=site_url('/wp-admin')?>" title="Two Tap products" class="button button-large button-next"><?php esc_html_e( 'WordPress Admin Dashboard', 'twotap' ); ?></a>
        <br>
        <br>
        <script src="<?=TT_PLUGIN_URL?>/admin/js/vendor/jquery.min.js"></script>

        <script type="text/javascript">
            var $ = jQuery.noConflict();
            jQuery(document).ready(function ($) {
                $(document).on('click', '.js-click-to-tweet', function () {
                    window.open( "http://twitter.com/intent/tweet?url=<?=$url?>&text=<?=urlencode($tweet)?>", "twitterwindow", "height=450, width=550, toolbar=0, location=0, menubar=0, directories=0, scrollbars=0" );

                });
            });
        </script>
        <?php
    }

    public function selected($option, $value)
    {
        echo $option == $value ? 'selected' : '';
    }
}
new Two_Tap_Admin_Setup_Wizard($plugin_name, $version);
