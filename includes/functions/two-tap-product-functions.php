<?php

/**
 * Figures out if it's a Two Tap product
 *
 * @param  integer $post_id Post ID.
 *
 * @return boolean
 */
function is_twotap_product($post_id){
    if(is_array($post_id)){
        if(isset($post_id['post_id'])){
            $post_id = $post_id['post_id'];
        }
        if(isset($post_id['product_id'])){
            $post_id = $post_id['product_id'];
        }
    }
    if(is_object($post_id)){
        if(isset($post_id->ID)){
            $post_id = $post_id->ID;
        } else {
            $post_id = $post_id->get_id();
        }
    }
    $post_id = ensure_post_parent($post_id);
    $type = get_post_type($post_id);
    $product = wc_get_product($post_id);
    if($type !== TT_POST_TYPE_PRODUCT){
        return false;
    }
    return has_term(TT_TERM_SLUG_PRODUCT, TT_TERM_TAXONOMY_PRODUCT, $post_id);
}

/**
 * Ensures that the post_id provided is really the parent
 *
 * @param  [type] $post_id [description]
 *
 * @return [type]          [description]
 */
function ensure_post_parent($post_id)
{
    $parent_id = wp_get_post_parent_id($post_id);
    return $parent_id ? $parent_id : $post_id;
}

/**
 * Creates a parseable URL hash
 *
 * @param  [type] $product_id [description]
 * @param  [type] $order_id   [description]
 *
 * @return [type]             [description]
 */
function twotap_create_random_url_hash($product_id = null, $order_id = null)
{
    $rand = rand(100000, 999999);
    $hash = '#plugin_woocommerce';
    if(!is_null($product_id)){
        $hash .= "_product_{$product_id}";
    }
    if(!is_null($order_id)){
        $hash .= "_order_{$order_id}";
    }
    $hash .= "_rand_{$rand}";
    return $hash;
}

/**
 * Parses the hash from an original_url
 *
 * @param  [type] $url [description]
 *
 * @return [type]      [description]
 */
function parse_hash($url = null)
{
    if(is_null($url) || !is_string($url)){
        return null;
    }
    $response = [];
    if(strpos($url, '#plugin_woocommerce_') !== false){
        if(strpos($url, '_order_') !== false){
            preg_match('/_order_([0-9]+)/', $url, $matches);
            $order_id = $matches[1];
            $response['order_id'] = $order_id;
        }
        // variation id
        if(strpos($url, '_product_') !== false){
            preg_match('/_product_([0-9]+)/', $url, $matches);
            $product_id = $matches[1];
            $response['product_id'] = $product_id;
        }
    } else {
        // let's try and find a product by the original url
        $product = get_product_by_twotap_original_url($url);
        $response['product_id'] = $product->ID;
    }

    return $response;
}

/**
 * Fetches a product by meta twotap_original_url
 *
 * @param  [type] $original_url [description]
 *
 * @return [type]               [description]
 */
function get_product_by_twotap_original_url($original_url = null){
    if( is_null($original_url) ){
        return false;
    }

    $args = array(
        'post_type' => 'product',
        'post_status' => twotap_product_statuses(),
        'posts_per_page' => 1000,
        'meta_query' => array(
            array(
                'key' => 'twotap_original_url',
                'value' => $original_url
            ),
        )
    );
    $query = new WP_Query($args);

    if($query->have_posts()){
        $present = $query->get_posts();
        $db_product = array_shift($present);
        return $db_product;
    } else {
        return false;
    }

}

/**
 * Fetches the product by the meta data twotap_site_id & twotap_product_md5
 *
 * @param  [type] $site_id     [description]
 * @param  [type] $product_md5 [description]
 *
 * @return [type]              [description]
 */
function get_product_by_site_id_and_product_md5($site_id = null, $product_md5 = null){
    if( is_null($site_id) || is_null($product_md5) ){
        return null;
    }

    $args = array(
        'post_type' => 'product',
        'post_status' => ['draft', 'publish'],
        'posts_per_page' => 1000,
        'meta_query' => array(
            array(
                'key' => 'twotap_product_md5',
                'value' => $product_md5
            ),
            array(
                'key' => 'twotap_site_id',
                'value' => $site_id
            ),
        )
    );
    $query = new WP_Query($args);

    if($query->have_posts()){
        $present = $query->get_posts();
        $db_cart = array_shift($present);
        return $db_cart;
    } else {
        return null;
    }

}