<?php

use GuzzleHttp\Client;

/**
 * TT_Admin class.
 */
class Two_Tap_Admin_Setup_Helper
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
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->define_constants();
        do_action( 'tt_admin_loaded' );

    }

    public function define_constants()
    {
        $this->define( 'TT_SIGN_UP_URL', TT_URL_CORE . '/buyers/zooxo1PhLa4iX5ieziuPhei9_from_ecom_plugins' );
    }

    public function twotap_setup_create_account()
    {
        $data = $_POST;

        if(!isset($data['email'])){
            wp_send_json([
                'success' => false,
                'message' => 'Please fill in your e-mail address.'
            ]);
            wp_die();
        }

        if(!isset($data['password'])){
            wp_send_json([
                'success' => false,
                'message' => 'Please fill in a password.'
            ]);
            wp_die();
        }

        if(isset($data['password']) && isset($data['password_confirmation']) && ($data['password'] != $data['password_confirmation'])){
            wp_send_json([
                'success' => false,
                'message' => 'The password and password confirmation fileds must be the same.'
            ]);
            wp_die();
        }

        if(!isset($data['name'])){
            wp_send_json([
                'success' => false,
                'message' => 'Please fill in your name.'
            ]);
            wp_die();
        }

        if(!isset($data['store_name'])){
            wp_send_json([
                'success' => false,
                'message' => 'Please fill in your store name.'
            ]);
            wp_die();
        }

        $client = new Client([
            'timeout'  => 5000,
            'headers' => [
                'Content-Type'     => 'application/json',
            ],
        ]);

        $body = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'password_confirmation' => $data['password_confirmation'],
            'store_name' => $data['store_name'],
            'platform' => 'woocommerce',
        ];

        try{
            $response = $client->request('GET', TT_SIGN_UP_URL, [ 'query' => $body ]);
            $response = json_decode($response->getBody(true));
        }catch (Exception $e){
            $response = $e->getResponse();
            wp_send_json(['success' => false, 'message' => 'Something went wrong with the request. Please try again later.', 'response' => $response]);
            wp_die();
        }

        if(!isset($response->message)){
            wp_send_json(['success' => false, 'message' => 'Something went wrong with the response. Please try again later.']);
            wp_die();
        }
        if( $response->message == 'email_already_exists' ){
            wp_send_json(['success' => false, 'message' => 'E-mail already exists.']);
            wp_die();
        }
        if( $response->message == 'done' ){
            // storing the tokens
            update_option( 'twotap_public_token', $response->public_token );
            update_option( 'twotap_private_token', $response->private_token );

            wp_send_json(['success' => true, 'message' => 'Succesfully created the account']);
            wp_die();
        }
    }

    public function twotap_setup_save_tokens()
    {
        $data = $_POST;

        if(!isset($data['public_token']) || $data['public_token'] == '' || !isset($data['private_token']) || $data['private_token'] == ''){
            wp_send_json([
                'success' => false,
                'message' => 'Please fill in your Two Tap tokens.'
            ]);
            wp_die();
        }

        update_option( 'twotap_public_token', $data['public_token'] );
        update_option( 'twotap_private_token', $data['private_token'] );

        wp_send_json([
            'success' => true,
            'message' => 'Succesfully set tokens.'
        ]);
        wp_die();
    }

    public function twotap_setup_save_wc_tokens()
    {
        $data = $_POST;

        if(!isset($data['consumer_key']) || $data['consumer_key'] == '' || !isset($data['consumer_secret']) || $data['consumer_secret'] == ''){
            wp_send_json([
                'success' => false,
                'message' => 'Please fill in your WooCommerce tokens.'
            ]);
            wp_die();
        }

        update_option( 'wc_consumer_key', $data['consumer_key'] );
        update_option( 'wc_consumer_secret', $data['consumer_secret'] );

        wp_send_json([
            'success' => true,
            'message' => 'Succesfully set WooCommerce tokens.'
        ]);
        wp_die();
    }

    public function twotap_setup_plugin_settings()
    {
        $data = $_POST;

        $logistics_type = get_option( 'twotap_logistics_type' );

        if(isset($data['plugin_settings']['logistics_type'])){
            update_option('twotap_logistics_type', $data['plugin_settings']['logistics_type']);
        }

        $fields = [ 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'country', 'telephone' ];
        $logistics_settings = get_option( 'twotap_logistics_settings' );

        foreach (['shipping', 'billing'] as $group) {
            foreach ($fields as $field) {
                if(isset($data['plugin_settings']["twotap_logistics_settings_{$group}_{$field}"])){
                    $logistics_settings["{$group}_{$field}"] = $data['plugin_settings']["twotap_logistics_settings_{$group}_{$field}"];
                }
            }
        }
        $logistics_settings['email'] = $data['plugin_settings']["twotap_logistics_settings_email"];
        update_option('twotap_logistics_settings', $logistics_settings);

        if(isset($data['plugin_settings']['twotap_markup_type']) && in_array($data['plugin_settings']['twotap_markup_type'], ['none', 'percent', 'value'])){
            update_option('twotap_markup_type', $data['plugin_settings']['twotap_markup_type']);
            l('type updated', $data['plugin_settings']['twotap_markup_type']);
        }
        if(isset($data['plugin_settings']['twotap_markup_value'])){
            update_option('twotap_markup_value', $data['plugin_settings']['twotap_markup_value']);
            l('type value updated', $data['plugin_settings']['twotap_markup_value']);
        }
        if(isset($data['plugin_settings']['intl_enabled'])){
            update_option('twotap_international_logistics_enabled', 'yes');
        }

        wp_send_json([
            'success' => true,
            'message' => 'Settings updated.'
        ]);
        wp_die();
    }

    public function twotap_get_plans()
    {
        $data = $_POST;

        if(!isset($data['info_type'])){
            wp_send_json([
                'success' => false,
                'message' => 'Bad request.'
            ]);
            wp_die();
        }

        $client = new Client([
            'timeout'  => 5000,
            'headers' => [
                'Content-Type'     => 'application/json',
            ],
        ]);

        try{
            $response = $client->request('GET', TT_URL_API . "/dropship/plans");
            $response = json_decode($response->getBody(true));
        }catch (Exception $e){
            $response = $e->getMessage();
            wp_send_json(['success' => false, 'message' => 'Something went wrong with the request. Please try again later. ' . $response]);
            wp_die();
        }
        if(!isset($response->message)){
            wp_send_json(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
            wp_die();
        }
        if( $response->message == 'failed' ){
            $message = isset($response->description) ? $response->description : 'No description message.';
            wp_send_json(['success' => false, 'message' => $message]);
            wp_die();
        }
        if( $response->message == 'done' ){
            if( !isset($response->plans) ){
                wp_send_json(['success' => false, 'message' => 'There are no available plans.']);
                wp_die();
            }
            wp_send_json(['success' => true, 'plans' => $response->plans]);
            wp_die();
        }

    }

    /**
     * WooCommerce keys callback route
     */
    public function add_woocommerce_keys_route()
    {
        register_rest_route( 'two_tap', '/woocommerce_keys', array(
            'methods' => 'POST',
            'callback' => [ $this, 'woocommerce_keys_callback' ],
        ));
    }

    /**
     * WooCommerce keys callback
     * @param  WP_REST_Request $request [description]
     * @return [type]                   [description]
     */
    public function woocommerce_keys_callback(WP_REST_Request $request)
    {
        $params = $request->get_params();

        if(!isset($params['consumer_key']) || $params['consumer_key'] == '' || !isset($params['consumer_secret']) || $params['consumer_secret'] == ''){
            l()->error('WooCommerce keys are invalid.');
            return;
        }

        update_option( 'wc_consumer_key', $params['consumer_key'] );
        update_option( 'wc_consumer_secret', $params['consumer_secret'] );

        l('WooCommerce keys updated in callback.');
    }

    public function define($name, $value){
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }
}