<?php

/**
 * Get the Two Tap products in WooCommerce orderby
 *
 * @param  [type] $order [description]
 *
 * @return [type]        [description]
 */
function get_twotap_products_in_order($order_id = null){
    if(is_null($order_id)){
        return false;
    }
    if(is_object($order_id)){
        $order = $order_id;
    } else {
        $order = wc_get_order($order_id);
    }
    if(is_null($order)){
        return false;
    }
    if(!$order){
        return [];
    }
    $products = $order->get_items();
    $twotap_products = array_filter($products, function($product){
        return $product->get_meta(TT_META_TWOTAP_STATUS, true);
    });
    return $twotap_products;
}

/**
 * Get the Two Tap products in WooCommerce orderby
 *
 * @param  [type] $order [description]
 *
 * @return [type]        [description]
 */
function is_twotap_order($order_id = null){
    if(is_null($order_id)){
        return false;
    }
    if(is_object($order_id)){
        $order = $order_id;
    } else {
        $order = wc_get_order($order_id);
    }
    if(is_null($order)){
        return false;
    }
    if(!$order){
        return [];
    }
    $products = $order->get_items();
    $twotap_products = array_filter($products, function($product){
        return $product->get_meta(TT_META_TWOTAP_STATUS, true);
    });
    return $twotap_products;
}

/**
 * Get the Two Tap purchase by the WooCommerce order ID
 *
 * @param  [type] $order_id [description]
 *
 * @return [type]           [description]
 */
function get_twotap_purchase_by_order_id($order_id = null)
{
    if( is_null($order_id) ){
        return false;
    }

    $args = array(
        'post_type' => TT_POST_TYPE_PURCHASE,
        'post_status' => twotap_db_purchase_statuses(),
        'posts_per_page' => 1000,
        'order' => 'DESC',
        'orderby' => 'post_modified',
        'meta_query' => array(
            array(
                'key' => TT_META_ORDER_ID,
                'value' => $order_id,
                'compare' => '=',
                )
            )
        );
    $query = new WP_Query($args);

    if($query->have_posts()){
        $present = $query->get_posts();
        $db_purchase = array_shift($present);
        return $db_purchase;
    } else {
        return false;
    }
}

/**
 * Get the WooCommerce order by Two Tap state.
 * Available states are 'pending' or 'sent'
 *
 * @param  string $status [description]
 *
 * @return [type]         [description]
 */
function get_orders_by_twotap_sent_state($state = '')
{
    if(!wc_api_enabled()){
        return false;
    }
    $wc_order_statuses = array_keys(wc_get_order_statuses());
    $args = [
        'post_type' => TT_POST_TYPE_ORDER,
        'post_status' => $wc_order_statuses,
        'posts_per_page' => 1000,
        'order' => 'DESC',
        'orderby' => 'post_modified',
        'meta_query' => [
        [
            'key' => TT_META_TWOTAP_SENT_STATE,
            'value' => $state,
            'compare' => '=',
            ]
        ]
    ];

    $query = new WP_Query($args);

    if($query->have_posts()){
        return $query->get_posts();
    } else {
        return false;
    }
}

function get_insufficient_funds_orders(){
    $wc_order_statuses = array_keys(wc_get_order_statuses());

    $args = [
        'post_type' => TT_POST_TYPE_ORDER,
        'post_status' => $wc_order_statuses,
        'posts_per_page' => 1000,
        'order' => 'DESC',
        'orderby' => 'post_modified',
        'meta_query' => [
            [
                'key' => TT_META_TWOTAP_INSUFFICIENT_FUNDS,
                'compare' => 'EXISTS'
            ]
        ]
    ];
    $query = new WP_Query($args);

    if($query->have_posts()){
        return $query->get_posts();
    } else {
        return [];
    }
}