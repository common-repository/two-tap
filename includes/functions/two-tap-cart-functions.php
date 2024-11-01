<?php

/**
 * Get the Two Tap cart associated with this WooCommerce order
 *
 * @param  WC_Order $order The WC_Order object.
 *
 * @return [type]        [description]
 */
function get_twotap_cart_for_order($order = null)
{
    if(is_null($order)){
        return false;
    }
    $last_cart = get_twotap_cart_by_order_id($order->get_id());
    if($last_cart){
        return $last_cart;
    }
    return false;
}

/**
 * Get the Two Tap cart associated with this WooCommerce cart
 *
 * @return mixed         Post or null.
 */
function get_twotap_cart_for_cart(){
    return get_post(WC()->session->get('twotap_cart_id'));
}

/**
 * Get Two Tap products from cart
 *
 * @param  WC_Cart $cart The cart from session.
 *
 * @return Array         The products.
 */
function get_twotap_products_in_cart($cart = null){
    if(is_null($cart)){
        if(is_null(WC()->cart)){
            return [];
        } else {
            $cart = WC()->cart;
        }
    }
    $products = $cart->get_cart();
    $twotap_products = array_filter($products, function($product){
        return is_twotap_product($product);
    });
    return $twotap_products;
}

/**
 * Get the non Two Tap products in WooCommerce cart
 *
 * @param  [type] $cart [description]
 *
 * @return [type]       [description]
 */
function get_regular_products_in_cart($cart = null){
    if(is_null($cart)){
        $cart = WC()->cart;
    }
    $products = $cart->get_cart();
    $regular_products = array_filter($products, function($product){
        return !is_twotap_product($product);
    });
    return $regular_products;
}

/**
 * Get the Two Tap cart by Two Tap cart_id
 *
 * @param  [type] $twotap_cart_id [description]
 *
 * @return [type]                 [description]
 */
function get_twotap_cart_by_twotap_cart_id($twotap_cart_id = null){
    if( is_null($twotap_cart_id) ){
        return false;
    }

    $args = array(
        'post_type' => TT_POST_TYPE_CART,
        'post_status' => twotap_db_cart_statuses(),
        'order' => 'DESC',
        'orderby' => 'post_modified',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'twotap_cart_id',
                'value' => $twotap_cart_id,
                'compare' => '=',
                )
            )
        );
    $query = new WP_Query($args);

    if($query->have_posts()){
        $present = $query->get_posts();
        $db_cart = array_shift($present);
        return $db_cart;
    } else {
        return false;
    }

}

/**
 * Get Two Tap cart by WC order ID
 *
 * @param  [type] $order_id [description]
 *
 * @return [type]           [description]
 */
function get_twotap_cart_by_order_id($order_id = null){
    if( is_null($order_id) ){
        return false;
    }

    $args = [
        'post_type' => TT_POST_TYPE_CART,
        'post_status' => twotap_db_cart_statuses(),
        'order' => 'DESC',
        'orderby' => 'post_modified',
        'posts_per_page' => 1,
        'meta_query' => [
        [
            'key' => TT_META_ORDER_ID,
            'value' => $order_id,
            'compare' => '=',
            ]
        ]
    ];
    $query = new WP_Query($args);

    if($query->have_posts()){
        $present = $query->get_posts();
        $db_cart = array_shift($present);
        return $db_cart;
    } else {
        return false;
    }
}

/**
 * Parses a cart status and retrieves the common available shipping options
 *
 * @param  [type] $cart_status [description]
 *
 * @return array               The available shipping methods
 */
function get_shipping_options_from_cart_status($cart_status)
{
    if(!isset($cart_status['sites']) || count($cart_status['sites']) == 0 ){
        return [];
    }
    $shipping_options = array_map(function($site){
        return array_keys($site['shipping_options']);
    }, $cart_status['sites']);

    $intersection = reset($shipping_options);
    foreach ($shipping_options as $option) {
        $intersection = array_intersect($intersection, $option);
    }
    return $intersection;
}

/**
 * Refresh Two Tap Cart status
 *
 * @param  [type] $twotap_cart_id [description]
 *
 * @return [type]                 [description]
 */
function twotap_refresh_cart_status($twotap_cart_id = null)
{
    if(is_null($twotap_cart_id)){
        return false;
    }

    $db_cart = get_twotap_cart_by_twotap_cart_id($twotap_cart_id);
    $post_id = $db_cart->ID;

    if(!tt_api_enabled()){
        return false;
    }

    global $tt_api;
    $cart_status = $tt_api->cart()->status($twotap_cart_id, false, TT_DESTINATION_COUNTRY);
    update_post_meta($post_id, TT_META_TWOTAP_LAST_STATUS, $cart_status);

    return $cart_status;
}

/**
 * Ensures that the cart status has been refreshed in ten last minute
 *
 * @param  [type] $twotap_cart_id [description]
 *
 * @return [type]                 [description]
 */
function ensure_cart_status_is_fresh($twotap_cart_id = null){
    if(is_null($twotap_cart_id)){
        return false;
    }
    $transient_key = TT_TRANSIENT_KEY_CART_STATUS_REFRESH_TIME . $twotap_cart_id;
    $transient = get_transient($transient_key);
    l('inainte de transient');
    if(!$transient){
        l('in de transient');
        twotap_refresh_cart_status($twotap_cart_id);
        set_transient($transient_key, 1, 60);
    }
}

