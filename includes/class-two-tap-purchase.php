<?php

use GuzzleHttp\Client;

/**
 * Main TwoTap Class.
 *
 * @class TwoTap
 * @version 1.0.0
 */
class Two_Tap_Purchase {

    /**
     * Two Tap API.
     *
     * @since    1.0.0
     * @access   private
     * @var      null|object $api Two Tap API.
     */
    private $api;

    /**
     * WooCommerce API
     *
     * @since    1.0.0
     * @access   private
     * @var      null|object $wc_api WooCommerce API.
     */
    private $wc_api;

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
    public function __construct( $plugin_name, $version ){
        global $tt_api;
        $this->api = $tt_api;

        global $wc_api;
        $this->wc_api = $wc_api;

        do_action( 'two_tap_purchase_loaded' );
    }

    /**
     * Add purchase confirm URL callback
     */
    public function add_purchase_confirm_url(){
        register_rest_route( 'two_tap', '/purchase_confirm_url', array(
            'methods' => 'POST',
            'callback' => [$this, 'purchase_confirmed_callback'],
        ));
    }

    /**
     * Add purchase update URL callback
     */
    public function add_purchase_update_url(){
        register_rest_route( 'two_tap', '/purchase_update_url', array(
            'methods' => 'POST',
            'callback' => [$this, 'purchase_updated_callback'],
        ));
    }

    /**
     * Add WooCommerce order created URL callback
     */
    public function add_wc_order_created_url(){
        register_rest_route( 'two_tap', '/wc_order_created', array(
            'methods' => 'POST',
            'callback' => [$this, 'wc_order_created_callback'],
        ));
    }

    /**
     * Purchase confirmed callback
     *
     * @param  WP_REST_Request $request [description]
     *
     * @return [type]                   [description]
     */
    public function purchase_confirmed_callback(WP_REST_Request $request)
    {
        do_action('twotap_purchase_confirmed_callback');
        $params = $request->get_params();
        l('purchase_confirmed_callback::: ', $params);
        $updated = $this->update_purchase_info($params);

        // sending the confirm to Two Tap
        $purchase_id = $params['purchase_id'];
        $response = $this->api->purchase()->confirm($purchase_id, TT_OPTION_PURCHASE_TEST_MODE);
        do_action('twotap_purchase_confirmed');

        wp_send_json(['message' => 'Thank you Two Tap!']);
        wp_die();
    }

    /**
     * Purchase updated callback
     *
     * @param  WP_REST_Request $request [description]
     *
     * @return [type]                   [description]
     */
    public function purchase_updated_callback(WP_REST_Request $request)
    {
        do_action('twotap_purchase_updated_callback');
        $params = $request->get_params();
        l('Purchase updated callback.', $params);

        $this->update_purchase_info($params);

        wp_send_json(['message' => 'Thank you Two Tap!']);
        wp_die();
    }

    /**
     * WooCommerce order created callback
     *
     * @param  WP_REST_Request $request [description]
     *
     * @return [type]                   [description]
     */
    public function wc_order_created_callback(WP_REST_Request $request)
    {
        $params = $request->get_params();
        l('Order created callback.', $params);

        if (!isset($params['id'])) {
            if (!isset($params['webhook_id'])) {
                // on webhook creation this route is accesed. Don't pollute the logs.
                l('Order response not ok.');
            }
            return;
        }

        /**
         * @todo Must check if there are Two Tap products in cart
         */

        $order_id = $params['id'];

        update_post_meta( $order_id, TT_META_TWOTAP_SENT_STATE, 'pending' );
        $this->create_purchase($order_id);

        wp_send_json(['message' => 'Thank you Two Tap!']);
        wp_die();
    }

    /**
     * Create a Two Tap purchase
     *
     * @param  [type] $order_id [description]
     *
     * @return [type]           [description]
     */
    public function create_purchase($order_id = null)
    {
        if ( !tt_plugin_enabled() ){
            return;
        }

        if (is_null($order_id)) {
            return;
        }

        // get order
        $order = wc_get_order($order_id);

        // try and find a twotap purchase with this wc order_id
        $existing_purchase = get_twotap_purchase_by_order_id($order_id);

        // we stop if we found a purchase
        if ($existing_purchase) {
            l()->error('Two Tap purchase already created.');
            return;
        }

        $products = $order->get_items();

        // get tt products
        $tt_products = array_filter($products, function($product){
            return is_twotap_product($product->get_product_id());
        });
        // l('herereee', [$products, $tt_products]);

        array_walk($tt_products, function($tt_product, $order_item_id){
            $post_id = ensure_post_parent($tt_product->get_product_id());
            $site_id = get_post_meta($post_id, TT_META_TWOTAP_SITE_ID, true);
            $product_md5 = get_post_meta($post_id, TT_META_TWOTAP_PRODUCT_MD5, true);
            wc_update_order_item_meta($order_item_id, TT_META_TWOTAP_STATUS, TT_STATUS_STILL_PROCESSING);
            wc_update_order_item_meta($order_item_id, TT_META_TWOTAP_SITE_ID, $site_id);
            wc_update_order_item_meta($order_item_id, TT_META_TWOTAP_PRODUCT_MD5, $product_md5);
        });

        l('Creating Two Tap cart from cart callback for purchase with $order_id.', $order_id);

        // create cart
        $tt_cart = new Two_Tap_Cart( $this->plugin_name, $this->version );
        $cart_id = $tt_cart->create_twotap_cart_from_order($order, $order_id);

    }

    /**
     * Create purchase form order ID
     *
     * @param  [type] $order_id [description]
     *
     * @return [type]           [description]
     */
    public function create_purchase_from_order_id($order_id = null)
    {

        if (is_null($order_id)) {
            l()->error("Order ID is null on creating purchase.");
            return;
        }

        l("Creating purchase from order_id: {$order_id}");

        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        $shipping_data = $order_data['shipping'];
        $country = 'US';
        if (isset($shipping_data['country'])) {
            $country = $shipping_data['country'];
        }
        $shipping_option = get_post_meta($order_id, 'twotap_chosen_shipping_option', true);

        $shipping_info = null;

        if ($country == 'US' ) {
            $shipping_info = null;
        }else{
            $logistics_type = get_option( 'twotap_logistics_type' );
            $logistics_settings = get_option( 'twotap_logistics_settings' );
            switch ($logistics_type) {
                case 'own_logistics':
                    $logistics_settings = get_option( 'twotap_logistics_settings' );
                    $shipping_info = $logistics_settings;
                break;
                case 'twotap_logistics':
                    # code...
                break;
            }
        }
        $fields_input = generate_fields_input($order, $shipping_info, $shipping_option);

        if (is_null($fields_input) || empty($fields_input)) {
            l()->error('$fields_input is empty or null.');
            return null;
        }

        $twotap_cart_id = get_post_meta($order_id, TT_META_TWOTAP_CART_ID, true);

        if (!$twotap_cart_id) {
            l("Couldn't find twotap_cart_id for order_id {$order_id}");
            return null;
        }

        /**
         * @todo must investigate what kind of cart ID is this
         */
        $db_cart = get_twotap_cart_by_twotap_cart_id($twotap_cart_id);

        if (!$db_cart) {
            l('Cart couldn\'t be found in create_purchase_from_order_id().');
            return null;
        }

        l("Creating purchase for cart_id: {$twotap_cart_id} & order_id: {$order_id}");
        $db_cart_id = $db_cart->ID;

        $twotap_cart_id = $db_cart->twotap_cart_id;
        $affiliate_links = null;
        $confirm = [
            "method" => "http",
            "http_confirm_url" => TT_URL_SITE . "/wp-json/two_tap/purchase_confirm_url",
            "http_update_url" => TT_URL_SITE . "/wp-json/two_tap/purchase_update_url",
        ];
        $products = null;
        $notes = null;
        $test_mode = TT_OPTION_PURCHASE_TEST_MODE;
        $locale = null;
        $user_token = null;
        $store_in_wallet = null;
        $proposed_recipe_id = null;
        $request_params = [
            'twotap_cart_id' => $twotap_cart_id,
            'fields_input' => $fields_input,
            'affiliate_links' => $affiliate_links,
            'confirm' => $confirm,
            'products' => $products,
            'notes' => $notes,
            'test_mode' => $test_mode,
            'locale' => $locale,
            'user_token' => $user_token,
            'store_in_wallet' => $store_in_wallet,
            'proposed_recipe_id' => $proposed_recipe_id
        ];

        if (isset($fields_input) && !empty($fields_input)) {
            // add purchase to db
            $this->add_twotap_purchase_to_db($request_params, $order_id);
        } else {
            l()->error('Failed purchase. $fields_input is empty or unset.');
        }

    }

    /**
     * Add Two Tap purchase to the DB
     *
     * @param [type] $request_params [description]
     * @param [type] $order_id       [description]
     */
    public function add_twotap_purchase_to_db($request_params, $order_id = null)
    {
        l('Adding purchase to DB.', [$request_params]);
        $purchase_id = null;
        $post = [
            'post_type' => TT_POST_TYPE_PURCHASE,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_status' => 'draft',
        ];
        $timestamp = time();
        $post['post_title'] = "Draft purchase - Order ID {$order_id}";
        $post['post_name'] = "draft-purchase-{$order_id}-{$timestamp}";

        $post_id = wp_insert_post( $post, true );
        update_post_meta( $post_id, TT_META_TWOTAP_REQUEST_PARAMS, $request_params );
        update_post_meta( $post_id, TT_META_TWOTAP_CART_ID, $request_params['twotap_cart_id'] );
        // update_post_meta( $post_id, TT_META_TWOTAP_SENT_STATE, 'pending' );

        if (!is_null($order_id)) {
            update_post_meta( $post_id, TT_META_ORDER_ID, $order_id );
            update_post_meta( $order_id, 'db_purchase_id', $post_id );
        }

        if (TT_AUTO_SEND_PURCHASE) {
            $this->make_purchase($post_id);
        }

        return $post_id;
    }

    /**
     * Refresh purchase status
     *
     * @return [type] [description]
     */
    public function ajax_refresh_purchase_status()
    {
        $post_id = $_POST['post_id'];

        if (!$post_id) {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid purchase ID.'
            ]);
            wp_die();
        }

        $purchase_id = get_post_meta($post_id, 'twotap_purchase_id', true);

        $refreshed = $this->refresh_purchase_status($purchase_id);

        wp_send_json([
            'success' => $refreshed,
        ]);
        wp_die();
    }

    /**
     * Get the user's Two Tap deposit value
     *
     * @return [type] [description]
     */
    public function get_twotap_deposit()
    {
        l('Fetching Two Tap deposit.');

        $client = new Client([
            'timeout'  => 5000,
            'headers' => [
                'Content-Type'     => 'application/json',
            ],
        ]);

        $tt_tokens = get_tt_tokens();

        $body = [
            'private_token' => $tt_tokens['twotap_private_token'],
        ];

        try {
            $response = $client->request('POST', TT_URL_API_APP_STATUS, [ 'form_params' => $body ]);
            $response = json_decode($response->getBody(true), true);
        } catch (Exception $e) {
            $response = $e->getResponse();
            l()->error('Couldn\'t fetch the Two Tap deposit.');
            return [
                'success' => false,
                'message' => 'Something went wrong with the request. Please try again later.',
                'description' => $response
            ];
        }

        $message = isset($response['message']) ? $response['message'] : '';
        $description = isset($response['description']) ? $response['description'] : '';
        $deposit = false;

        if ($response['message'] == 'done' && isset($response['deposit'])) {
            $deposit = $response['deposit'];
        }

        return [
            'deposit' => $deposit,
            'message' => $message,
            'description' => $description,
        ];
    }

    /**
     * Ajax action to send purchase to Two Tap servers
     *
     * @return [type] [description]
     */
    public function ajax_send_purchase()
    {
        $post_id = $_POST['post_id'];
        if (!$post_id) {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid purchase ID.'
            ]);
            wp_die();
        }

        $purchase_response = $this->make_purchase($post_id);

        if (!$purchase_response) {
            wp_send_json([
                'success' => false,
                'message' => 'Purchase couldn\'t be made.'
            ]);
            wp_die();
        }

        wp_send_json($purchase_response);
        wp_die();
    }

    /**
     * Send the purchase to Two Tap servers
     *
     * @param  [type] $post_id [description]
     *
     * @return [type]          [description]
     */
    public function make_purchase($post_id = null)
    {
        if (is_null($post_id)) {
            return false;
        }

        $db_purchase = get_post($post_id);
        $request_params = get_post_meta($post_id, TT_META_TWOTAP_REQUEST_PARAMS, true);
        if (!isset($request_params) || $request_params == false) {
            l()->error('No request params in make_purchase()');
            return false;
        }

        extract($request_params);
        // dd($twotap_cart_id, $fields_input, $affiliate_links, $confirm, $products, $notes, $test_mode, $locale, $u ser_token, $store_in_wallet, $proposed_recipe_id);

        $order_id = get_post_meta($post_id, TT_META_ORDER_ID, true);
        update_post_meta($order_id, 'db_purchase_id', $post_id);
        $db_cart = get_twotap_cart_by_twotap_cart_id($twotap_cart_id);
        $twotap_deposit = $this->get_twotap_deposit();
        $deposit_amount = $twotap_deposit['deposit'];

        if ($deposit_amount === false) {
            return [
                'success' => false,
                'message' => $response['message'],
                'description' => $response['description'],
            ];
        }

        $cart_status = get_post_meta($db_cart->ID, TT_META_TWOTAP_LAST_STATUS, true);
        $estimate = get_post_meta($order_id, TT_META_TWOTAP_ESTIMATE, true);

        $final_price = $estimate['estimated_total_prices']['final_price'];
        $final_price = sanitize_price($final_price);

        l("Final price {$final_price} vs deposit {$deposit_amount}");

        if ($deposit_amount < $final_price) {
            // not enough funds

            // mark insufficient funds
            update_post_meta($order_id, TT_META_TWOTAP_INSUFFICIENT_FUNDS, true);
            // send mail
            $tt_mail = new Two_Tap_Mail( $this->plugin_name, $this->version );
            $tt_mail->send('insufficient_funds');

            return [
                'success' => false,
                'message' => "You have insufficient funds for this purchase. Purchase value {$final_price} vs deposit {$deposit_amount}",
            ];
        } else {
            delete_post_meta($order_id, TT_META_TWOTAP_INSUFFICIENT_FUNDS);
        }

        // checking to see if the logistics settings are correct
        if ( !logistics_settings_valid() ) {
            // mark Two Tap status
            update_post_meta($order_id, TT_META_TWOTAP_LOGISTICS_FAILED, true);

            // send mail
            $tt_mail = new Two_Tap_Mail( $this->plugin_name, $this->version );
            $tt_mail->send('logistics_settings_failed');

            return [
                'success' => false,
                'message' => "You have invalid logistics settings.",
            ];
        } else {
            delete_post_meta($order_id, TT_META_TWOTAP_LOGISTICS_FAILED);
        }

        // make the purchase
        $discounts_applied = get_post_meta($db_cart->ID, TT_META_TWOTAP_DISCOUNTS_APPLIED, true);
        $should_apply_discounts = apply_filters('twotap_should_apply_discounts', true);
        if ( !$discounts_applied && $should_apply_discounts ) {
            // apply discounts
            $discounts = [
                [
                    'source' => 'deposit',
                    'category' => 'percentage_off',
                    'amount' => 100
                ],
                [
                    'source' => 'deposit',
                    'category' => 'free_shipping',
                    'amount' => 100
                ]
            ];

            l("Setting discounts.");
            $discounts_response = $this->api->cart()->discounts($twotap_cart_id, $discounts);
            l("Discounts response.", $discounts_response);

            if ($discounts_response['message'] != 'done') {
                l('Bad /cart/discounts response.', $discounts_response);
                update_post_meta($db_cart->ID, TT_META_TWOTAP_DISCOUNTS_APPLIED, false);
                return [
                    'success' => false,
                    'message' => $discounts_response['description'],
                ];
            } else {
                update_post_meta($db_cart->ID, TT_META_TWOTAP_DISCOUNTS_APPLIED, true);
            }
        }

        $order = wc_get_order($order_id);
        $data = $order->get_data();
        $shipping_data = $data['shipping'];
        $country = 'US';
        if (isset($shipping_data['country'])) {
            $country = $shipping_data['country'];
        }
        if ($country != 'US') {
            // noauthCheckout
            $logistics_type = get_option( 'twotap_logistics_type' );
            $shipping_info = get_option( 'twotap_logistics_settings' );
            $customer_info = logistics_info_to_fields_input($shipping_info);

            // registering own address for the purchase
            if ($logistics_type == 'own_logistics') {
                foreach ($fields_input as $site_id => $info) {
                    $fields_input[$site_id]['noauthCheckout'] = $customer_info;
                }
            }
        }

        $purchase_response = $this->api->purchase()->create($twotap_cart_id, $fields_input, $affiliate_links, $confirm, $products, $notes, $test_mode, $locale, $user_token, $store_in_wallet, $proposed_recipe_id);
        l('Purchase response.', $purchase_response);

        update_post_meta( $post_id, TT_META_TWOTAP_LAST_STATUS, $purchase_response );

        if (isset($purchase_response['purchase_id'])) {
            update_post_meta( $post_id, TT_META_TWOTAP_PURCHASE_ID, $purchase_response['purchase_id'] );
            $this->refresh_purchase_status($purchase_response['purchase_id']);
            $post = [
                'ID' => $post_id,
                'post_title' => "Purchase ID {$purchase_response['purchase_id']}",
                'post_name' => "purchase-id-{$purchase_response['purchase_id']}",
                'post_status' => twotap_status_to_wp($purchase_response['message']),
            ];

            $updated = wp_update_post($post, false);
            l('Purchase udpated.', $updated);
        }
        update_post_meta($post_id, TT_META_TWOTAP_SENT_STATE, 'sent');

        $response = [];

        if (isset($purchase_response['purchase_id'])) {
            $response['success'] = true;
            $response['purchase_response'] = $purchase_response;
        } else {
            $response['success'] = false;
            $response['purchase_response'] = $purchase_response;
        }

        return $response;
    }

    /**
     * Update the Two Tap purchase info
     *
     * @param  [type] $purchase [description]
     *
     * @return [type]           [description]
     */
    public function update_purchase_info($purchase = null)
    {
        if (is_null($purchase)) {
            return false;
        }

        if (!isset($purchase['purchase_id'])) {
            l('Bad purchase. ', $purchase);
            return false;
        }

        $db_purchase = get_twotap_purchase($purchase['purchase_id']);
        if (!$db_purchase) {
            l('Purchase couldn\'t be found. ', $purchase);
            return false;
        }

        l('Updating purchase', $purchase['purchase_id']);

        // updating the status & the last response
        $db_purchase->post_status = twotap_status_to_wp($purchase['message']);
        wp_update_post($db_purchase, false);
        $updated = update_post_meta($db_purchase->ID, TT_META_TWOTAP_LAST_STATUS, $purchase);

        $purchase_notes = json_decode($purchase['notes'], true);
        $order_id = $purchase_notes[TT_META_ORDER_ID];
        $order = wc_get_order($order_id);

        $items = $order->get_items();
        $twotap_products = [];
        foreach ($items as $item) {
            $twotap_products[$item->get_variation_id()] = $item;
        }

        $parsed_twotap_products = [];
        $twotap_product = new Two_Tap_Product($this->plugin_name, $this->version);

        if (isset($purchase['sites']) && count($purchase['sites']) > 0) {
            foreach($purchase['sites'] as $site_id => $site_response){

                // checking for failed products
                $failed_product_md5 = isset($site_response['failed_to_add_to_cart']) ? $site_response['failed_to_add_to_cart'] : false;

                // updating the rest meta date
                if (isset($site_response['products']) && count($site_response['products']) > 0) {
                    foreach($site_response['products'] as $product_md5 => $product_response){
                        $product_info = parse_hash($product_response['url']);
                        if (isset($product_info['product_id'])) {
                             if ( isset($twotap_products[$product_info['product_id']])) {
                                $order_item = $twotap_products[$product_info['product_id']];
                                if ($failed_product_md5 && $failed_product_md5 == $product_md5) {
                                    $parent_product_id = $order_item->get_product_id();
                                    $twotap_product->remove_product($parent_product_id);
                                }
                                $parsed_twotap_products[$product_info['product_id']] = $order_item;
                                wc_update_order_item_meta($twotap_products[$product_info['product_id']]->get_id(), TT_META_TWOTAP_STATUS, $product_response['status']);
                            }
                        }
                    }
                }
            }
        }

        if ($updated) {
            do_action('twotap_purchase_updated');
        }

        return $updated;
    }

    /**
     * Refreshing the Two Tap purchase status
     *
     * @param  [type] $purchase_id [description]
     *
     * @return [type]              [description]
     */
    public function refresh_purchase_status($purchase_id = null)
    {
        if (is_null($purchase_id)) {
            return false;
        }
        $db_purchase = get_twotap_purchase($purchase_id);
        $post_id = $db_purchase->ID;

        $purchase_status = $this->api->purchase()->status($purchase_id);
        l('$purchase_status', $purchase_status);
        update_post_meta($post_id, TT_META_TWOTAP_LAST_STATUS, $purchase_status);

        return true;
    }

}