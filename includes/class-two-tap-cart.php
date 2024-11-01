<?php

/**
 * Main TwoTap Class.
 *
 * @class TwoTap
 * @version 1.0.0
 */
class Two_Tap_Cart {

    protected $api;

    protected $wc_api;

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

        global $tt_api;
        $this->api = $tt_api;

        global $wc_api;
        $this->wc_api = $wc_api;

        do_action( 'two_tap_cart_loaded' );
    }

    /**
     * Adds the cart finished callback
     */
    public function add_cart_finished_url(){
        register_rest_route( 'two_tap', '/cart_finished_url', array(
            'methods' => 'POST',
            'callback' => [ $this, 'cart_finished_callback' ],
        ));
    }

    public function cart_finished_callback(WP_REST_Request $request)
    {
        do_action( 'two_tap_cart_cart_finished_callback' );

        $params = $request->get_params();
        l("Cart finished callback.", $params);
        $order_id = null;

        // find cart
        $twotap_cart_id = isset($params['cart_id']) ? $params['cart_id'] : null;
        $notes = json_decode($params['notes'], true);
        if(isset($notes['order_id'])){
            $order_id = $notes['order_id'];
        }

        $db_cart = get_twotap_cart_by_twotap_cart_id($twotap_cart_id);

        if($db_cart) {
            l("Found cart with Two Tap cart_id.", $twotap_cart_id);

            $post_id = $db_cart->ID;
            update_post_meta($post_id, TT_META_TWOTAP_LAST_RESPONSE, $params);
            $db_cart->post_status = twotap_status_to_wp($params['message']);
            wp_update_post((array)$db_cart, false);

            // get cart/status
            $this->refresh_cart_status($twotap_cart_id);

            if(!is_null($order_id)){
                update_post_meta($post_id, TT_META_ORDER_ID, $order_id);
                update_post_meta($order_id, TT_META_TWOTAP_CART_ID, $twotap_cart_id);
                $tt_purchase = new Two_Tap_Purchase( $this->plugin_name, $this->version );
                $tt_purchase->create_purchase_from_order_id($order_id);
            }
        } else {
            l()->error("Cart not found in the DB.", $twotap_cart_id);
            return null;
        }
        wp_send_json(['message' => 'Thank you Two Tap!']);
    }

    /**
     * Add product to cart callback
     *
     * @param [type]  $cart_item_data [description]
     * @param [type]  $post_id        [description]
     * @param integer $vartiation_id  [description]
     */
    public function add_cart_item($cart_item_data, $post_id, $vartiation_id = 0)
    {
        l('Adding cart item.', [$cart_item_data, $post_id]);
        if(is_twotap_product($post_id)){
            $cart_item_data['twotap_product'] = true;
        }
        return $cart_item_data;
    }

    /**
     * Add product to cart callback
     */
    public function add_to_cart()
    {
        $twotap_cart_id = $this->refresh_twotap_cart();
        l('Added cart item.', $twotap_cart_id);
    }

    /**
     * Remove product from cart callback
     *
     * @param  [type] $cart_item_key [description]
     * @param  [type] $that          [description]
     *
     * @return [type]                [description]
     */
    public function remove_cart_item($cart_item_key, $that)
    {
        $twotap_cart_id = $this->refresh_twotap_cart();
        l('Removed cart item.', $twotap_cart_id);
    }

    /**
     * Cart updated callback
     *
     * @param  [type] $cart_updated [description]
     *
     * @return [type]               [description]
     */
    public function cart_updated($cart_updated)
    {
        $twotap_cart_id = $this->refresh_twotap_cart();
        l('Cart updated.', $twotap_cart_id);
    }

    public function refresh_twotap_cart()
    {
        // don't create tt cart if there are no tt products in wc cart
        if(count(get_twotap_products_in_cart(WC()->cart)) == 0){
            return;
        }

        $twotap_cart_id = $this->create_twotap_cart_from_wc_cart(WC()->cart);
        $last_cart = WC()->session->get('twotap_cart_id');
        WC()->session->set('twotap_cart_id', $twotap_cart_id);
        if($last_cart){
            $post = [
                'ID' => $last_cart,
                'post_status' => 'draft'
            ];
            wp_update_post($post);
        }
        return $twotap_cart_id;
    }

    /**
     * Create a Two Tap cart from WooCommerce cart
     *
     * @param  [type] $cart [description]
     *
     * @return [type]       [description]
     */
    public function create_twotap_cart_from_wc_cart($cart = null)
    {
        l('Attempting to create a Two Tap cart from wc_cart.', $cart);

        if( is_null($cart) ){
            l()->error('$cart is null.');
            return null;
        }

        // don't create tt cart if there are no tt products in wc cart
        if(count(get_twotap_products_in_cart($cart)) == 0){
            return;
        }

        $items = $cart->get_cart();

        $chosen_attributes = $this->create_chosen_attributes_from_cart($cart);
        extract($chosen_attributes);

        $finished_url = TT_URL_SITE . "/wp-json/two_tap/cart_finished_url";
        $format = null;
        $notes = null;
        $test_mode = null;
        $cache_time = -1;
        $destination_country = TT_DESTINATION_COUNTRY;

        return $this->create_tt_cart($urls, $finished_url, $format, $notes, $test_mode, $cache_time, $destination_country, $cart_products);
    }

    /**
     * Create a Two Tap cart and send it to the DB
     *
     * @param  array  $product_urls [description]
     * @return [type]               [description]
     **/
    public function create_tt_cart($urls = [], $finished_url = TT_URL_SITE . "/wp-json/two_tap/cart_finished_url", $format = null, $notes = null, $test_mode = null, $cache_time = -1, $destination_country = TT_DESTINATION_COUNTRY, $cart_products = [])
    {
        if ( !tt_plugin_enabled() ){
            return;
        }

        $request_params = [
            'urls' => $urls,
            'finished_url' => $finished_url,
            'format' => $format,
            'notes' => $notes,
            'test_mode' => $test_mode,
            'cache_time' => $cache_time,
            'destination_country' => $destination_country,
        ];

        $cart_response = $this->api->cart()->create($urls, $finished_url, $format, $notes, $test_mode, $cache_time, $destination_country);

        l('Cart response: ', $cart_response);

        $cart_id = $this->add_twotap_cart_to_db($cart_response, $request_params, $cart_products);

        return $cart_id;
    }

    /**
     * Create an anonymous cart to refresh product info
     *
     * @param  array  $urls [description]
     *
     * @return [type]       [description]
     */
    public function create_anonymus_cart($urls = [])
    {

        if(count($urls) == 0){
            return;
        }

        l('Creating an anonymous cart.', $urls);

        $finished_url = null;
        $format = null;
        $notes = null;
        $test_mode = null;
        $cache_time = -1;
        $destination_country = null;

        $this->api->cart()->create($urls, $finished_url, $format, $notes, $test_mode, $cache_time, $destination_country);
    }

    /**
     * Creating a Two Tap cart from WooCommerce order
     *
     * @param  [type] $order [description]
     *
     * @return [type]        [description]
     */
    public function create_twotap_cart_from_order($order = null)
    {
        l('Attempting to create a Two Tap cart from order.', [$order]);

        if( is_null($order) ){
            l()->error('$order is null.');
            return null;
        }

        $order_id = $order->get_id();

        $chosen_attributes = $this->create_chosen_attributes_from_order($order);
        extract($chosen_attributes);

        $finished_url = TT_URL_SITE . "/wp-json/two_tap/cart_finished_url";
        $format = null;
        $notes = null;
        $test_mode = null;
        $cache_time = -1;
        $destination_country = TT_DESTINATION_COUNTRY;
        if(!is_null($order_id)){
            $notes = json_encode((array)[
                'order_id' => $order_id,
            ]);
        }

        return $this->create_tt_cart($urls, $finished_url, $format, $notes, $test_mode, $cache_time, $destination_country, $cart_products);
    }

    /**
     * Create a chosen attributes array from WooCommerce cart
     *
     * @param  [type] $cart [description]
     *
     * @return [type]       [description]
     */
    public function create_chosen_attributes_from_cart($cart = null)
    {
        $items = $cart->get_cart();

        $cart_products = [];
        $urls = [];

        if(!empty($items)){
            foreach($items as $item){
                $product = $item['data'];
                if($product->is_type('variation')){
                    $parent_id = $product->get_parent_id();
                } else {
                    $parent_id = $product->get_id();
                }
                if(!is_twotap_product($parent_id)){
                    continue;
                }

                $url = get_post_meta($parent_id, TT_META_TWOTAP_ORIGINAL_URL, true);
                $url = $url . twotap_create_random_url_hash($product->get_id());
                $original_md5 = md5($url);
                $twotap_product_variation_values = $product->get_meta(TT_META_TWOTAP_VARIATION_VALUES, true);
                if($twotap_product_variation_values){
                    foreach ($twotap_product_variation_values as $attribute_key => $attribute_array) {
                        $cart_products[$original_md5]['chosen_attributes'][$attribute_key] = $attribute_array['value'];
                    }
                }
                $cart_products[$original_md5]['chosen_attributes']['quantity'] = $item['quantity'];
                $cart_products[$original_md5]['product_id'] = $product->get_id();

                $urls[] = $url;
            }

            // removing empty values, duplicates and stripping keys
            $urls = array_values(array_filter($urls));
        }

        return [
            'cart_products' => $cart_products,
            'urls' => $urls
        ];
    }

    /**
     * Create a chosen attributes array from WooCommerce cart
     *
     * @param  [type] $order [description]
     *
     * @return [type]        [description]
     */
    public function create_chosen_attributes_from_order($order = null)
    {
        $items = $order->get_items();

        $cart_products = [];
        $urls = [];

        if(!empty($items)){
            foreach ($items as $order_item_id => $order_item_product) {
                $product = $order_item_product->get_product();
                if($product->is_type('variation')){
                    $parent_id = $product->get_parent_id();
                } else {
                    $parent_id = $product->get_id();
                }
                if(!is_twotap_product($parent_id)){
                    continue;
                }

                $url = get_post_meta($parent_id, 'twotap_original_url', true);
                $url = $url . twotap_create_random_url_hash($product->get_id(), $order->get_id());
                $original_md5 = md5($url);

                $cart_products[$original_md5]['chosen_attributes']['quantity'] = $order_item_product->get_data()['quantity'];
                $cart_products[$original_md5]['product_id'] = $product->get_id();
                // if it's a variation, get the chosen attributes
                if($parent_id != $product->get_id()){
                    $twotap_product_variation_values = $product->get_meta('twotap_product_variation_values', true);
                    if($twotap_product_variation_values){
                        foreach ($twotap_product_variation_values as $attribute_key => $attribute_array) {
                            $cart_products[$original_md5]['chosen_attributes'][$attribute_key] = $attribute_array['value'];
                        }
                    }
                }

                $urls[] = $url;
            }

            // removing empty values, duplicates and stripping keys
            $urls = array_values(array_filter($urls));
        }

        return [
            'cart_products' => $cart_products,
            'urls' => $urls
        ];
    }

    /**
     * Adds Two Tap cart to DB
     *
     * @param [type] $cart_response  [description]
     * @param [type] $request_params [description]
     * @param array  $cart_products  [description]
     * @param [type] $order_id       [description]
     */
    protected function add_twotap_cart_to_db($cart_response, $request_params, $cart_products = [], $order_id = null)
    {
        $post = array(
            'post_content' => $cart_response['description'],
            'post_status' => twotap_status_to_wp($cart_response['message']),
            'post_title' => "Cart ID #{$cart_response['cart_id']}",
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_type' => TT_POST_TYPE_CART,
        );

        // Create post
        $post_id = wp_insert_post( $post, false );

        update_post_meta( $post_id, TT_META_TWOTAP_CART_ID, $cart_response['cart_id'] );
        update_post_meta( $post_id, TT_META_TWOTAP_LAST_STATUS, $cart_response);
        update_post_meta( $post_id, TT_META_TWOTAP_CART_PRODUCTS, $cart_products);
        update_post_meta( $post_id, TT_META_TWOTAP_REQUEST_PARAMS, $request_params);

        if(!is_null($order_id)){
            update_post_meta( $post_id, TT_META_ORDER_ID, $order_id );
        }

        return $post_id;
    }

    /**
     * Ajax bridge to refresh cart status
     *
     * @return [type] [description]
     */
    public function refresh_cart_status_ajax()
    {
        $data = $_POST;
        array_filter($data);
        unset($data['action']);

        $refreshed = $this->refresh_cart_status($data['cart_id']);
        wp_send_json( [ 'success' => $refreshed ] );
        wp_die();
    }

    /**
     * Refresh Two Tap Cart status
     *
     * @param  [type] $twotap_cart_id [description]
     *
     * @return [type]                 [description]
     */
    public function refresh_cart_status($twotap_cart_id = null)
    {
        if(is_null($twotap_cart_id)){
            return false;
        }

        twotap_refresh_cart_status($twotap_cart_id);

        $db_cart = get_twotap_cart_by_twotap_cart_id($twotap_cart_id);
        $post_id = $db_cart->ID;

        $cart_status = $this->api->cart()->status($twotap_cart_id, false, TT_DESTINATION_COUNTRY);
        update_post_meta($post_id, TT_META_TWOTAP_LAST_STATUS, $cart_status);

        $this->check_cart_response_products($cart_status);

        return true;
    }

    /**
     * Checks the cart for failed_to_add_to_cart items
     * and refreshes product info.
     *
     * Also runs an anonymous cart with the products original urls
     *
     * @param  [type] $cart_status [description]
     *
     * @return [type]              [description]
     */
    public function check_cart_response_products($cart_status)
    {
        l('Checking cart response products.');
        $failed_to_add_to_cart = [];
        $add_to_cart = [];
        $product_urls = [];

        foreach($cart_status['sites'] as $site_id => $site_response){
            if(isset($site_response['add_to_cart'])){
                foreach($site_response['add_to_cart'] as $product_md5 => $product_response){
                    $product_info = parse_hash($product_response['original_url']);
                    if(isset($product_info['product_id'])){
                        $add_to_cart[$product_info['product_id']] = $product_response;
                    } else {
                        l()->error('Couldn\'t parse url for cart product in add_to_cart.', ['original_url' => $product_response['original_url'], 'cart_status' => $cart_status]);
                    }
                }
            }
            if(isset($site_response['failed_to_add_to_cart'])){
                foreach($site_response['failed_to_add_to_cart'] as $product_md5 => $product_response){
                    $product_info = parse_hash($product_response['original_url']);
                    if(isset($product_info['product_id'])){
                        $failed_to_add_to_cart[] = $product_info['product_id'];
                    }elseif(isset($product_info['products'])){
                        foreach ($product_info['products'] as $db_product) {
                            $failed_to_add_to_cart[] = $db_product->ID;
                        }
                    } else {
                        l()->error('Couldn\'t parse url for cart product.', $cart_status);
                    }
                }
            }
        }

        if(count($add_to_cart) > 0){
            l()->warning('Trying to refresh products.', array_keys($add_to_cart));
            $tt_product = new Two_Tap_Product( $this->plugin_name, $this->version );
            foreach ($add_to_cart as $post_id => $product_response) {
                $parent_id = wp_get_post_parent_id($post_id);
                $post_id = $parent_id ? $parent_id : $post_id;
                $transient_key = 'twotap_product_refreshed_in_cart_' . $post_id;
                if(!get_transient($transient_key)){
                    l('Actually refreshing product.', $post_id);
                    $product_md5 = get_post_meta($post_id, 'twotap_product_md5', true);
                    $site_id = get_post_meta($post_id, 'twotap_site_id', true);
                    $original_url = get_post_meta($post_id, 'twotap_original_url', true);
                    $product_info = [
                        'product_md5' => $product_md5,
                        'site_id' => $site_id,
                    ];
                    // caching for 10 minutes
                    set_transient($transient_key, true, HOUR_IN_SECONDS / 6);
                    $tt_product->update_product_and_variations($post_id, $product_response);

                    $product_urls[] = $original_url;
                }
            }
        }
        if(count($failed_to_add_to_cart) > 0){
            l()->warning('There are OOS products.', $failed_to_add_to_cart);
            $tt_product = new Two_Tap_Product( $this->plugin_name, $this->version );
            foreach ($failed_to_add_to_cart as $post_id) {
                // automatically oos product in wc because the hashed md5 is different from the original one. also create cart with first url
                $tt_product->remove_product($post_id);
                $original_url = get_post_meta($post_id, 'twotap_original_url', true);
                $product_urls[] = $original_url;
            }
        }

        // create anonymus cart to update the info for the original url product
        $this->create_anonymus_cart($product_urls);

    }

}